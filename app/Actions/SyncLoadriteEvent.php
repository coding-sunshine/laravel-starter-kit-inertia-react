<?php

declare(strict_types=1);

namespace App\Actions;

use App\Events\LoadriteEventBroadcast;
use App\Events\WagonWeightUpdated;
use App\Models\Rake;
use App\Models\WagonLoading;
use App\Services\Loadrite\LoadriteUserDataParser;
use App\Services\Loadrite\WagonCapacityResolver;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;

final readonly class SyncLoadriteEvent
{
    /**
     * Persist a Loadrite event into loadrite_events idempotently, then recompute
     * the affected wagon's accumulated weight from SUM(Add) - SUM(Subtract) and
     * mirror that total into wagon_loading.loaded_quantity_mt (+ loadrite_weight_mt)
     * unless an operator override or weighbridge value should win.
     *
     * @param  array<string, mixed>  $event  Raw Loadrite API event
     * @return bool True if the event was newly inserted (recompute performed).
     */
    /** Minimum Short Total weight that counts as a real wagon completion (MT).
     *  Below this, treat as operator misfire / aborted load / test. */
    public const MIN_VALID_SHORT_TOTAL_MT = 30.0;

    /** A rake is considered "actively loading" only if it has wagon_loading
     *  activity within this many hours of the incoming event. Past that, the
     *  rake is treated as abandoned and the resolver looks for a newer one. */
    public const ACTIVE_WINDOW_HOURS = 6;

    public function __construct(
        private LoadriteUserDataParser $userData,
        private WagonCapacityResolver $capacity,
    ) {}

    public function handle(array $event, int $sidingId): bool
    {
        $eventId = $this->stringField($event, 'Id');
        $sequence = isset($event['Sequence']) ? (int) $event['Sequence'] : 0;
        $weightRaw = $event['Weight'] ?? null;
        $eventType = $this->stringField($event, 'Event');

        if ($eventId === null || $weightRaw === null || $eventType === null) {
            return false;
        }

        $weightMt = (float) $weightRaw;

        // Operators key the rake number, wagon number and wagon type into the
        // event's UserData fields. Parse them up front — they drive both rake
        // attribution and wagon identity.
        $parsed = $this->userData->parse($event);

        // Resolve which rake at this siding owns this event. The operator-keyed
        // rake number (UserData) is the most reliable signal; the time window
        // is the fallback.
        $rakeId = $this->resolveRakeIdForEvent(
            $sidingId,
            isset($event['Time']) ? Carbon::parse($event['Time']) : null,
            $parsed['rake_number'],
        );

        $inserted = $this->upsertEvent($event, $sidingId, $rakeId, null, $sequence, $eventType, $weightMt);

        if (! $inserted) {
            return false; // Already processed.
        }

        // Broadcast for /control-panel-2 per-event animations (bucket dump,
        // bulldozer slide). Legacy /control-room ignores this channel.
        LoadriteEventBroadcast::dispatch(
            sidingId: $sidingId,
            rakeId: $rakeId,
            wagonId: null,
            wagonSequence: $sequence,
            eventId: $eventId,
            eventType: $eventType,
            weightMt: $weightMt,
            eventTime: isset($event['Time']) ? Carbon::parse($event['Time'])->toIso8601String() : null,
            operator: $this->stringField($event, 'Operator'),
            scaleId: $this->stringField($event, 'Scale ID'),
        );

        // Each "Short Total" event = one wagon's final weight at completion.
        // Filter operator-error events (too light = aborted/test) and skip
        // duplicates from operators pressing the Short Total button twice.
        if ($eventType === 'Short Total' && $rakeId !== null) {
            if ($weightMt < self::MIN_VALID_SHORT_TOTAL_MT) {
                Log::info('loadrite: skipping low-weight Short Total (likely operator misfire)', [
                    'event_id' => $eventId,
                    'weight_mt' => $weightMt,
                    'siding_id' => $sidingId,
                ]);

                return true;
            }

            if ($this->isDuplicateShortTotal($event, $sidingId, $eventId)) {
                Log::info('loadrite: skipping duplicate Short Total for same session', [
                    'event_id' => $eventId,
                    'siding_id' => $sidingId,
                ]);

                return true;
            }

            $this->attributeShortTotal(
                $rakeId,
                $weightMt,
                $eventId,
                $parsed['wagon_number'],
                $parsed['wagon_type'],
            );
        }

        return true;
    }

    /**
     * Locate the rake this event should attach to.
     *
     * Strategy (in order):
     *  0. Operator-keyed rake number — if the event's UserData carries a rake
     *     number, match it directly against rakes.rake_serial_number at this
     *     siding. This is the operator telling us exactly which rake it is.
     *  1. Active rake — any rake at this siding with wagon_loading activity
     *     within ACTIVE_WINDOW_HOURS of the event AND an open loading window
     *     at event_time.
     *  2. Newest pending rake — when no rake is "active", pick the most recent
     *     pending/placed rake at the siding by id.
     *  3. Safety fallback — newest non-completed rake at the siding.
     */
    public function resolveRakeIdForEvent(int $sidingId, ?Carbon $eventTime, ?string $rakeNumber = null): ?int
    {
        if ($rakeNumber !== null && $rakeNumber !== '') {
            $bySerial = $this->findRakeBySerialNumber($sidingId, $rakeNumber);
            if ($bySerial !== null) {
                return $bySerial;
            }
        }

        if ($eventTime !== null) {
            $activeId = $this->findActiveRakeId($sidingId, $eventTime);
            if ($activeId !== null) {
                return $activeId;
            }

            $newestId = $this->findNewestPendingRakeId($sidingId, $eventTime);
            if ($newestId !== null) {
                return $newestId;
            }
        }

        $fallback = Rake::query()
            ->where('siding_id', $sidingId)
            ->where(function ($q): void {
                $q->whereNull('state')->orWhereNotIn('state', ['cancelled', 'dispatched', 'completed']);
            })
            ->when($eventTime !== null, fn ($q) => $q->where(function ($q2) use ($eventTime): void {
                $q2->whereNull('loading_end_time')->orWhere('loading_end_time', '>=', $eventTime);
            }))
            ->latest('id')
            ->first(['id']);

        return $fallback ? (int) $fallback->id : null;
    }

    /**
     * Insert a placeholder wagon_loading row for every wagon in the rake that
     * doesn't already have one. Idempotent — safe to call before any
     * attribution. Returns the number of new rows inserted.
     *
     * Public so that reattribution / one-off scripts can call it without
     * driving a full Short Total flow.
     */
    public function ensureWagonLoadingRowsExist(int $rakeId): int
    {
        $existing = DB::table('wagon_loading')
            ->where('rake_id', $rakeId)
            ->pluck('wagon_id')
            ->all();

        $missing = DB::table('wagons')
            ->where('rake_id', $rakeId)
            ->when($existing !== [], fn ($q) => $q->whereNotIn('id', $existing))
            ->pluck('id')
            ->all();

        if ($missing === []) {
            return 0;
        }

        $now = now();
        $rows = [];
        foreach ($missing as $wagonId) {
            $rows[] = [
                'rake_id' => $rakeId,
                'wagon_id' => (int) $wagonId,
                'loaded_quantity_mt' => 0,
                'loadrite_override' => false,
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }

        // Chunk to keep statement size sane on big rakes.
        $inserted = 0;
        foreach (array_chunk($rows, 200) as $chunk) {
            DB::table('wagon_loading')->insert($chunk);
            $inserted += count($chunk);
        }

        return $inserted;
    }

    /**
     * Step 0 — match the operator-keyed rake number against
     * rakes.rake_serial_number. rake_serial_number is the railway's rake
     * number (what the Weighments page calls "Rake Number"). It can repeat
     * across years, so prefer a non-completed rake at this siding, newest id.
     */
    private function findRakeBySerialNumber(int $sidingId, string $rakeNumber): ?int
    {
        $rake = Rake::query()
            ->where('siding_id', $sidingId)
            ->where('rake_serial_number', $rakeNumber)
            ->where(function ($q): void {
                $q->whereNull('state')->orWhereNotIn('state', ['cancelled', 'dispatched']);
            })
            ->orderByDesc('id')
            ->first(['id']);

        return $rake ? (int) $rake->id : null;
    }

    /**
     * Step 1 — a rake counts as "active" if it has wagon_loading activity in
     * the recent window AT the event time and its loading window is open.
     */
    private function findActiveRakeId(int $sidingId, Carbon $eventTime): ?int
    {
        $cutoff = $eventTime->copy()->subHours(self::ACTIVE_WINDOW_HOURS);

        $rake = Rake::query()
            ->where('siding_id', $sidingId)
            ->where(function ($q) use ($eventTime): void {
                $q->whereNull('loading_end_time')
                    ->orWhere('loading_end_time', '>=', $eventTime);
            })
            ->whereExists(function ($q) use ($cutoff): void {
                $q->select(DB::raw(1))
                    ->from('wagon_loading')
                    ->whereColumn('wagon_loading.rake_id', 'rakes.id')
                    ->where('wagon_loading.updated_at', '>=', $cutoff);
            })
            ->orderByRaw(
                '(SELECT MAX(updated_at) FROM wagon_loading wl WHERE wl.rake_id = rakes.id) DESC',
            )
            ->first(['id']);

        return $rake ? (int) $rake->id : null;
    }

    /**
     * Step 2 — newest pending/placed rake at the siding. Does NOT require
     * wagon_loading rows (key difference from the legacy fallback); rows are
     * created lazily during attribution. Newest-by-id is the operator's most
     * recent rake at the siding.
     */
    private function findNewestPendingRakeId(int $sidingId, Carbon $eventTime): ?int
    {
        $rake = Rake::query()
            ->where('siding_id', $sidingId)
            ->where(function ($q): void {
                $q->whereNull('state')->orWhereNotIn('state', ['cancelled', 'dispatched', 'completed']);
            })
            ->where(function ($q) use ($eventTime): void {
                $q->whereNull('loading_end_time')
                    ->orWhere('loading_end_time', '>=', $eventTime);
            })
            ->orderByDesc('id')
            ->first(['id']);

        return $rake ? (int) $rake->id : null;
    }

    /**
     * Detect an operator pressing "Short Total" twice on the same wagon.
     * Same scale + user_data2 + user_data3 within a 30-minute window = dupe.
     */
    private function isDuplicateShortTotal(array $event, int $sidingId, string $eventId): bool
    {
        $scaleId = $this->stringField($event, 'Scale ID');
        $ud2 = $this->stringField($event, 'UserData2');
        $ud3 = $this->stringField($event, 'UserData3');

        if ($scaleId === null) {
            return false;
        }

        $eventTime = isset($event['Time']) ? Carbon::parse($event['Time']) : now();

        return DB::table('loadrite_events')
            ->where('event_type', 'Short Total')
            ->where('siding_id', $sidingId)
            ->where('scale_id', $scaleId)
            ->where('user_data2', $ud2)
            ->where('user_data3', $ud3)
            ->where('event_id', '!=', $eventId)
            ->where('event_time', '>=', $eventTime->copy()->subMinutes(30))
            ->where('event_time', '<=', $eventTime)
            ->exists();
    }

    /**
     * Bind a Short Total event (one completed wagon's final weight) to the next
     * unfilled wagon in the rake (lowest wagon_sequence with no loadrite weight
     * yet, no manual override, not a weighbridge entry).
     *
     * If the event carried a wagon number / type in its UserData, the wagon's
     * real identity (number, type, carrying capacity) is stamped onto the
     * wagon row, replacing the W1..W59 placeholders.
     */
    private function attributeShortTotal(
        int $rakeId,
        float $weightMt,
        string $shortTotalEventId,
        ?string $wagonNumber = null,
        ?string $typeAbbr = null,
    ): void {
        // Brand-new rakes (operator created the rake + wagons but never
        // "placed" it, so no per-wagon loading rows exist yet) need their
        // wagon_loading scaffolding before we can attribute anything.
        $this->ensureWagonLoadingRowsExist($rakeId);

        // Find the next unfilled wagon in this rake (lowest sequence with no
        // Loadrite weight yet, not overridden, not from weighbridge).
        $row = DB::table('wagon_loading')
            ->join('wagons', 'wagons.id', '=', 'wagon_loading.wagon_id')
            ->where('wagon_loading.rake_id', $rakeId)
            ->where(function ($q) {
                $q->whereNull('wagon_loading.loadrite_weight_mt')
                    ->orWhereRaw('wagon_loading.loadrite_weight_mt::numeric = 0');
            })
            ->where('wagon_loading.loadrite_override', false)
            ->where(function ($q) {
                $q->whereNull('wagon_loading.weight_source')
                    ->orWhere('wagon_loading.weight_source', '!=', 'weighbridge');
            })
            ->orderBy('wagons.wagon_sequence')
            ->select('wagon_loading.id as wl_id', 'wagons.id as wagon_id', 'wagons.wagon_sequence', 'wagons.wagon_number', 'wagons.wagon_type', 'wagons.pcc_weight_mt', 'wagon_loading.cc_capacity_mt', 'wagon_loading.remarks', 'wagon_loading.rake_id')
            ->first();

        if (! $row) {
            return;
        }

        // Stamp the wagon's real identity from the Loadrite UserData.
        $pcc = $this->stampWagonIdentity($row, $wagonNumber, $typeAbbr);

        DB::table('wagon_loading')
            ->where('id', $row->wl_id)
            ->update([
                'loadrite_weight_mt' => $weightMt,
                'loaded_quantity_mt' => $weightMt,
                'weight_source' => 'loadrite',
                'loadrite_last_synced_at' => now(),
                'remarks' => mb_trim(($row->remarks ? $row->remarks.' ' : '').'[loadrite:'.$shortTotalEventId.']'),
                'updated_at' => now(),
            ]);

        $sidingId = (int) DB::table('rakes')->where('id', $row->rake_id)->value('siding_id');

        WagonWeightUpdated::dispatch(
            sidingId: $sidingId,
            wagonId: (int) $row->wagon_id,
            sequence: (int) $row->wagon_sequence,
            loadriteWeightMt: $weightMt,
            weightSource: 'loadrite',
            percentage: $pcc > 0 ? round(($weightMt / $pcc) * 100, 1) : 0.0,
            status: $weightMt >= $pcc && $pcc > 0 ? 'overload' : 'loaded',
        );
    }

    /**
     * Write the wagon's real identity — number, type, carrying capacity — onto
     * the wagons row from the Loadrite-keyed values, replacing W1..W59
     * placeholders. Returns the effective carrying capacity (MT).
     *
     * @param  object{wagon_id: int, wagon_number: ?string, wagon_type: ?string, pcc_weight_mt: mixed}  $row
     */
    private function stampWagonIdentity(object $row, ?string $wagonNumber, ?string $typeAbbr): float
    {
        $existingPcc = (float) ($row->pcc_weight_mt ?? 0);

        if ($wagonNumber === null && $typeAbbr === null) {
            return $existingPcc;
        }

        $cap = $this->capacity->resolve($wagonNumber, $typeAbbr);
        $update = [];

        // Replace placeholder wagon numbers (W1, W12, …) or empty values; never
        // overwrite a real number an operator may have entered manually.
        $isPlaceholder = $row->wagon_number === null
            || preg_match('/^W\d+$/i', (string) $row->wagon_number) === 1;
        if ($wagonNumber !== null && $isPlaceholder) {
            $update['wagon_number'] = $wagonNumber;
        }

        // Fill in the wagon type when we resolved one and the row has none.
        if (($row->wagon_type === null || $row->wagon_type === '') && $cap['type'] !== null) {
            $update['wagon_type'] = $cap['type'];
        }

        // CC from the fleet (wagon-match or type-modal) supersedes the backfill
        // estimate — it is traceable to a real registered wagon.
        $effectivePcc = $existingPcc;
        if ($cap['cc'] !== null && $cap['cc'] > 0) {
            $update['pcc_weight_mt'] = $cap['cc'];
            $effectivePcc = $cap['cc'];
        }

        if ($update !== []) {
            $update['updated_at'] = now();
            DB::table('wagons')->where('id', $row->wagon_id)->update($update);
        }

        return $effectivePcc;
    }

    private function resolveWagonLoading(int $sidingId, int $sequence, array $event): ?WagonLoading
    {
        $eventTime = isset($event['Time']) ? Carbon::parse($event['Time']) : null;
        $rakeId = $this->resolveRakeIdForEvent($sidingId, $eventTime);

        if ($rakeId === null) {
            return null;
        }

        return WagonLoading::query()
            ->with('wagon:id,wagon_sequence,wagon_number,pcc_weight_mt')
            ->where('rake_id', $rakeId)
            ->whereHas('wagon', fn ($q) => $q->where('wagon_sequence', $sequence))
            ->first();
    }

    /**
     * Insert event idempotently by event_id. Returns true if newly inserted.
     */
    private function upsertEvent(
        array $event,
        int $sidingId,
        ?int $rakeId,
        ?int $wagonId,
        int $sequence,
        string $eventType,
        float $weightMt,
    ): bool {
        $eventId = $this->stringField($event, 'Id') ?? '';

        $row = [
            'event_id' => $eventId,
            'siding_id' => $sidingId,
            'rake_id' => $rakeId,
            'wagon_id' => $wagonId,
            'wagon_sequence' => $sequence,
            'event_type' => $eventType,
            'weight_mt' => $weightMt,
            'event_time' => isset($event['Time']) ? Carbon::parse($event['Time']) : null,
            'operator' => $this->stringField($event, 'Operator'),
            'scale_id' => $this->stringField($event, 'Scale ID'),
            'product' => $this->stringField($event, 'Product'),
            'gps_lat' => isset($event['GPSLatitude']) ? (float) $event['GPSLatitude'] : null,
            'gps_lng' => isset($event['GPSLongitude']) ? (float) $event['GPSLongitude'] : null,
            'user_data1' => $this->stringField($event, 'UserData1'),
            'user_data2' => $this->stringField($event, 'UserData2'),
            'user_data3' => $this->stringField($event, 'UserData3'),
            'raw_payload' => json_encode($event),
            'created_at' => now(),
            'updated_at' => now(),
        ];

        try {
            $affected = DB::table('loadrite_events')->insertOrIgnore($row);

            return $affected > 0;
        } catch (Throwable $e) {
            Log::error('loadrite event insert failed', [
                'event_id' => $eventId,
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    private function stringField(array $event, string $key): ?string
    {
        if (! isset($event[$key])) {
            return null;
        }

        $value = (string) $event[$key];

        return $value === '' ? null : $value;
    }
}
