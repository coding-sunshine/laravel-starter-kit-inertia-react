<?php

declare(strict_types=1);

namespace App\Actions;

use App\Events\WagonWeightUpdated;
use App\Models\LoadriteEvent;
use App\Models\Rake;
use App\Models\WagonLoading;
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
    public function handle(array $event, int $sidingId): bool
    {
        $eventId = $this->stringField($event, 'Id');
        $sequence = isset($event['Sequence']) ? (int) $event['Sequence'] : null;
        $weightRaw = $event['Weight'] ?? null;
        $eventType = $this->stringField($event, 'Event');

        if ($eventId === null || $sequence === null || $weightRaw === null || $eventType === null) {
            return false;
        }

        // Tare/zero-check events use Sequence 0 — never map to a wagon.
        if ($sequence <= 0) {
            return false;
        }

        $weightMt = (float) $weightRaw;

        // Locate the wagon_loading row (and thereby rake + wagon).
        $wagonLoading = $this->resolveWagonLoading($sidingId, $sequence, $event);

        if (! $wagonLoading || ! $wagonLoading->wagon) {
            // Still persist the event for audit; rake_id/wagon_id stay null.
            $this->upsertEvent($event, $sidingId, null, null, $sequence, $eventType, $weightMt);

            return false;
        }

        $eventInserted = $this->upsertEvent(
            $event,
            $sidingId,
            $wagonLoading->rake_id,
            $wagonLoading->wagon_id,
            $sequence,
            $eventType,
            $weightMt,
        );

        if (! $eventInserted) {
            return false; // Already processed — no-op.
        }

        // Weighbridge readings are legal/billing truth; never overwrite with Loadrite.
        if ($wagonLoading->weight_source === 'weighbridge') {
            return true;
        }

        // Recompute cumulative for this (rake, wagon_sequence) from the events table.
        $cumulative = (float) LoadriteEvent::query()
            ->where('rake_id', $wagonLoading->rake_id)
            ->where('wagon_sequence', $sequence)
            ->selectRaw("COALESCE(SUM(CASE WHEN event_type = 'Add' THEN weight_mt ELSE -weight_mt END), 0) as total")
            ->value('total');

        $updates = [
            'loadrite_weight_mt' => $cumulative,
            'loadrite_last_synced_at' => now(),
        ];

        // Mirror to loaded_quantity_mt so existing readers see live cumulative numbers.
        // Skip mirror if operator override is active — manual entry stays authoritative.
        if (! $wagonLoading->loadrite_override) {
            $updates['weight_source'] = 'loadrite';
            $updates['loaded_quantity_mt'] = $cumulative;
        }

        $wagonLoading->update($updates);
        $refreshed = $wagonLoading->fresh();

        WagonWeightUpdated::dispatch(
            sidingId: $sidingId,
            wagonId: $wagonLoading->wagon_id,
            sequence: $sequence,
            loadriteWeightMt: $cumulative,
            weightSource: $refreshed->weight_source,
            percentage: $wagonLoading->cc_capacity_mt > 0
                ? round(($cumulative / (float) $wagonLoading->cc_capacity_mt) * 100, 1)
                : 0.0,
            status: $refreshed->weight_source === 'weighbridge' ? 'loaded' : 'loading',
        );

        return true;
    }

    /**
     * Locate the wagon_loading row that this event should attach to.
     *
     * Strategy: match the rake whose virtual loading window contains the event
     * timestamp. Virtual anchor = placement_time OR earliest wagon_loading.created_at
     * for the rake (the field is often null on this dataset). Virtual end =
     * loading_end_time OR open-ended. Last resort: most recent active rake.
     */
    public function resolveRakeIdForEvent(int $sidingId, ?Carbon $eventTime): ?int
    {
        if ($eventTime !== null) {
            $rake = Rake::query()
                ->where('siding_id', $sidingId)
                ->has('wagonLoadings')
                ->whereRaw(
                    'COALESCE(placement_time, (SELECT MIN(created_at) FROM wagon_loading wl WHERE wl.rake_id = rakes.id)) <= ?',
                    [$eventTime],
                )
                ->where(function ($q) use ($eventTime): void {
                    $q->whereNull('loading_end_time')
                        ->orWhere('loading_end_time', '>=', $eventTime);
                })
                ->orderByRaw(
                    'COALESCE(placement_time, (SELECT MIN(created_at) FROM wagon_loading wl WHERE wl.rake_id = rakes.id)) DESC',
                )
                ->first(['id']);

            if ($rake) {
                return (int) $rake->id;
            }
        }

        $fallback = Rake::query()
            ->where('siding_id', $sidingId)
            ->whereIn('state', ['loading', 'placed', 'pending'])
            ->has('wagonLoadings')
            ->latest('id')
            ->first(['id']);

        return $fallback ? (int) $fallback->id : null;
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
