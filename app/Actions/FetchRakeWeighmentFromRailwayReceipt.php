<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\Rake;
use App\Models\RakeWagonWeighment;
use App\Models\RakeWeighment;
use App\Models\RrDocument;
use App\Models\RrWagonSnapshot;
use App\Models\Wagon;
use App\Services\RakeWeighmentPdfImporter;
use App\Support\DuplicateRrPdfToWeighmentStorage;
use App\Support\RakeWeighmentNetWeightValidator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use InvalidArgumentException;
use Throwable;

final readonly class FetchRakeWeighmentFromRailwayReceipt
{
    private const float WAGON_WEIGHT_MAX = 999.99;

    public function __construct(
        private DuplicateRrPdfToWeighmentStorage $duplicateRrPdf,
        private RakeWeighmentPdfImporter $rakeWeighmentPdfImporter,
        private ApplyWeighmentPenaltiesAction $applyWeighmentPenalties,
        private ApplyPloPenaltyAction $applyPloPenalty,
        private UpdateStockLedger $updateStockLedger,
    ) {}

    public function handle(Rake $rake, int $userId): RakeWeighment
    {
        $rake->loadMissing('siding');

        $rrDocument = RrDocument::query()
            ->where('rake_id', $rake->id)
            ->whereHas('wagonSnapshots')
            ->orderByRaw('CASE WHEN diverrt_destination_id IS NULL THEN 0 ELSE 1 END')
            ->orderByDesc('id')
            ->first();

        if ($rrDocument === null) {
            throw new InvalidArgumentException('No RR with wagon data uploaded for this rake yet.');
        }

        /** @var Collection<int, RrWagonSnapshot> $snapshots */
        $snapshots = $rrDocument->wagonSnapshots()
            ->orderBy('wagon_sequence')
            ->orderBy('id')
            ->get();

        if ($snapshots->isEmpty()) {
            throw new InvalidArgumentException('No RR with wagon data uploaded for this rake yet.');
        }

        $numericSum = 0.0;

        foreach ($snapshots as $snapshot) {
            $lw = $snapshot->loaded_weight_mt;

            if ($lw !== null && $lw !== '' && is_numeric($lw)) {
                $numericSum += (float) $lw;
            }
        }

        $totalNetMt = round($numericSum, 2);

        $payload = RakeWeighmentNetWeightValidator::payloadFromRrSnapshots($snapshots, $totalNetMt);
        RakeWeighmentNetWeightValidator::assertNonNegative($payload['totals'], $payload['wagon_rows']);
        RakeWeighmentNetWeightValidator::assertMinimumTotalNetWeight($payload['totals']);

        $storedPath = '';

        try {
            return DB::transaction(function () use (&$storedPath, $rake, $rrDocument, $snapshots, $totalNetMt, $userId): RakeWeighment {
                $rake->loadMissing('siding');

                $existing = $rake->rakeWeighments()
                    ->withCount('rakeWagonWeighments')
                    ->orderBy('id')
                    ->lockForUpdate()
                    ->first();

                if ($existing !== null && $existing->rake_wagon_weighments_count > 0) {
                    throw new InvalidArgumentException('A weighment has already been uploaded for this rake.');
                }

                $isMerge = $existing !== null;
                $mergeOldNetMt = 0.0;

                if ($isMerge && $existing !== null) {
                    $mergeOldNetMt = $existing->total_net_weight_mt !== null
                        ? round((float) $existing->total_net_weight_mt, 2)
                        : 0.0;
                }

                $storedPath = $this->duplicateRrPdf->handle($rrDocument);

                $fallbackDate = $rrDocument->rr_received_date;

                if ($fallbackDate === null && $rake->loading_date !== null) {
                    $fallbackDate = Date::parse($rake->loading_date->toDateString());
                }

                $fromStation = $rrDocument->from_station_code;

                if (! is_string($fromStation) || mb_trim($fromStation) === '') {
                    $fromStation = $rake->siding?->station_code ?? $rake->siding?->code ?? null;
                }

                $toStation = $rrDocument->to_station_code;

                if (! is_string($toStation) || mb_trim($toStation) === '') {
                    $toStation = $rake->destination_code ?? null;
                }

                $priorityNumber = $rake->priority_number !== null ? (string) $rake->priority_number : null;

                $rake->load(['wagons' => fn ($q) => $q->orderBy('wagon_sequence')]);

                if ($rake->wagons->isEmpty()) {
                    $this->createWagonsFromRrSnapshots($rake, $snapshots);
                    $rake->load(['wagons' => fn ($q) => $q->orderBy('wagon_sequence')]);
                }

                $wagons = $rake->wagons;

                $weighmentAttributes = [
                    'rake_id' => $rake->id,
                    'attempt_no' => 1,
                    'gross_weighment_datetime' => $fallbackDate,
                    'tare_weighment_datetime' => $fallbackDate,
                    'train_name' => null,
                    'direction' => null,
                    'commodity' => null,
                    'from_station' => $fromStation,
                    'to_station' => $toStation,
                    'priority_number' => $priorityNumber,
                    'total_gross_weight_mt' => null,
                    'total_tare_weight_mt' => null,
                    'total_net_weight_mt' => $totalNetMt,
                    'total_cc_weight_mt' => null,
                    'total_under_load_mt' => null,
                    'total_over_load_mt' => null,
                    'maximum_train_speed_kmph' => null,
                    'maximum_weight_mt' => null,
                    'pdf_file_path' => $storedPath,
                    'rr_document_id' => $rrDocument->id,
                    'status' => 'success',
                ];

                if ($isMerge && $existing !== null) {
                    $existing->update($weighmentAttributes);
                    $existing->refresh();
                    $weighment = $existing;
                } else {
                    $weighmentAttributes['created_by'] = $userId;
                    $weighment = RakeWeighment::query()->create($weighmentAttributes);
                }

                $wagonsBySequence = [];
                $wagonsByNumber = [];

                foreach ($wagons as $w) {
                    $wagonsBySequence[(int) $w->wagon_sequence] = $w;
                    $wagonsByNumber[mb_trim((string) $w->wagon_number)] = $w;
                }

                $totalUnderMt = 0.0;
                $totalOverMt = 0.0;

                foreach ($snapshots as $snapshot) {
                    $seq = (int) ($snapshot->wagon_sequence ?? 0);
                    $wagonNumber = mb_trim((string) ($snapshot->wagon_number ?? ''));
                    $wagon = $wagonsBySequence[$seq] ?? ($wagonNumber !== '' ? ($wagonsByNumber[$wagonNumber] ?? null) : null);

                    $netMt = null;

                    if ($snapshot->loaded_weight_mt !== null && $snapshot->loaded_weight_mt !== '' && is_numeric($snapshot->loaded_weight_mt)) {
                        $netMt = round((float) $snapshot->loaded_weight_mt, 2);
                    }

                    $ccMt = null;

                    if ($snapshot->permissible_weight_mt !== null && $snapshot->permissible_weight_mt !== '' && is_numeric($snapshot->permissible_weight_mt)) {
                        $ccMt = round((float) $snapshot->permissible_weight_mt, 2);
                    }

                    $printedTareMt = null;

                    if ($snapshot->tare_weight_mt !== null && $snapshot->tare_weight_mt !== '' && is_numeric($snapshot->tare_weight_mt)) {
                        $printedTareMt = round((float) $snapshot->tare_weight_mt, 2);
                    }

                    $actualGrossMt = null;

                    if ($snapshot->gross_weight_mt !== null && $snapshot->gross_weight_mt !== '' && is_numeric($snapshot->gross_weight_mt)) {
                        $actualGrossMt = round((float) $snapshot->gross_weight_mt, 2);
                    }

                    $overLoadMt = $this->clampSnapshotWeight($snapshot->overload_weight_mt);

                    $underLoadMt = null;

                    if ($ccMt !== null && $netMt !== null && $netMt < $ccMt) {
                        $underLoadMt = round($ccMt - $netMt, 2);
                    }

                    if ($overLoadMt !== null) {
                        $totalOverMt += $overLoadMt;
                    }

                    if ($underLoadMt !== null) {
                        $totalUnderMt += $underLoadMt;
                    }

                    RakeWagonWeighment::query()->create([
                        'rake_weighment_id' => $weighment->id,
                        'wagon_id' => $wagon?->id,
                        'wagon_number' => $wagonNumber !== '' ? $wagonNumber : $wagon?->wagon_number,
                        'wagon_sequence' => $seq > 0 ? $seq : $wagon?->wagon_sequence,
                        'wagon_type' => $snapshot->wagon_type,
                        'cc_capacity_mt' => $ccMt,
                        'printed_tare_mt' => $printedTareMt,
                        'actual_gross_mt' => $actualGrossMt,
                        'net_weight_mt' => $netMt,
                        'under_load_mt' => $underLoadMt,
                        'over_load_mt' => $overLoadMt,
                    ]);
                }

                $weighment->update([
                    'total_under_load_mt' => $totalUnderMt > 0 ? round($totalUnderMt, 2) : null,
                    'total_over_load_mt' => $totalOverMt > 0 ? round($totalOverMt, 2) : null,
                ]);
                $weighment->refresh();

                $this->rakeWeighmentPdfImporter->syncWagonsFromRakeWeighment($weighment);

                $rake->update([
                    'data_source' => 'system',
                    'loaded_weight_mt' => $weighment->total_net_weight_mt,
                    'under_load_mt' => $weighment->total_under_load_mt,
                    'over_load_mt' => $weighment->total_over_load_mt,
                ]);

                $siding = $rake->siding;
                $quantity = $weighment->total_net_weight_mt !== null ? (float) $weighment->total_net_weight_mt : 0.0;

                if (! $isMerge && $siding !== null && $quantity > 0.0) {
                    $this->updateStockLedger->recordDispatch(
                        $siding,
                        $quantity,
                        $rake->id,
                        'Rake weighment from RR',
                        $userId,
                        $weighment->id,
                    );
                }

                if ($isMerge && $siding !== null) {
                    $newNetMt = $weighment->total_net_weight_mt !== null
                        ? round((float) $weighment->total_net_weight_mt, 2)
                        : 0.0;
                    $deltaMt = round($newNetMt - $mergeOldNetMt, 2);

                    if (abs($deltaMt) >= 0.005) {
                        $this->updateStockLedger->applyRakeWeighmentNetDelta(
                            $siding,
                            (int) $rake->id,
                            (int) $weighment->id,
                            $deltaMt,
                            $userId,
                            'Rake weighment merged from RR',
                        );
                    }
                }

                /** @var RakeWeighment $weighmentFresh */
                $weighmentFresh = $weighment->fresh(['rakeWagonWeighments.wagon']);

                $this->applyWeighmentPenalties->handle($rake, $weighmentFresh);
                $this->applyPloPenalty->handle($rake, $weighmentFresh);

                /** @var RakeWeighment $result */
                $result = $weighmentFresh->fresh(['rakeWagonWeighments.wagon']);

                return $result;
            });
        } catch (Throwable $e) {
            if ($storedPath !== '') {
                Storage::disk('public')->delete($storedPath);
            }

            throw $e;
        }
    }

    /**
     * @param  Collection<int, RrWagonSnapshot>  $snapshots
     */
    private function createWagonsFromRrSnapshots(Rake $rake, Collection $snapshots): void
    {
        if ($snapshots->isEmpty()) {
            throw new InvalidArgumentException('RR data has no wagon rows to create wagons from.');
        }

        $seen = [];
        $autoSeq = 0;

        foreach ($snapshots as $snapshot) {
            $wagonNumber = mb_trim((string) ($snapshot->wagon_number ?? ''));

            if ($wagonNumber === '') {
                throw new InvalidArgumentException('RR data contains a wagon row with an empty wagon number.');
            }

            if (mb_strlen($wagonNumber) < 5) {
                Log::warning('FetchRakeWeighmentFromRailwayReceipt: RR wagon number shorter than PDF importer minimum', [
                    'rake_id' => $rake->id,
                    'wagon_number' => $wagonNumber,
                ]);
            }

            $seq = (int) ($snapshot->wagon_sequence ?? 0);

            if ($seq <= 0) {
                $autoSeq++;
                $seq = $autoSeq;
            }

            $dedupeKey = $seq.'|'.$wagonNumber;

            if (isset($seen[$dedupeKey])) {
                continue;
            }

            $seen[$dedupeKey] = true;

            $pccMt = null;

            if ($snapshot->permissible_weight_mt !== null && $snapshot->permissible_weight_mt !== '' && is_numeric($snapshot->permissible_weight_mt)) {
                $pccMt = round((float) $snapshot->permissible_weight_mt, 2);
            }

            $wagonType = $snapshot->wagon_type;

            if ($wagonType !== null && $wagonType !== '') {
                $wagonType = mb_trim((string) $wagonType);

                if ($wagonType === '') {
                    $wagonType = null;
                }
            } else {
                $wagonType = null;
            }

            Wagon::query()->create([
                'rake_id' => $rake->id,
                'wagon_sequence' => $seq,
                'wagon_number' => $wagonNumber,
                'wagon_type' => $wagonType,
                'tare_weight_mt' => null,
                'pcc_weight_mt' => $pccMt,
                'is_unfit' => false,
                'state' => 'pending',
            ]);
        }

        $count = Wagon::query()->where('rake_id', $rake->id)->count();
        $rake->update(['wagon_count' => $count]);
    }

    /**
     * Align wagon overload snapshots with {@see \App\Services\Railway\RrImportService::clampWagonWeight} bounds.
     */
    private function clampSnapshotWeight(mixed $value): ?float
    {
        if ($value === null || $value === '') {
            return null;
        }

        if (! is_numeric($value)) {
            return null;
        }

        $f = (float) $value;

        if ($f < 0 || $f > self::WAGON_WEIGHT_MAX) {
            return null;
        }

        return round($f, 2);
    }
}
