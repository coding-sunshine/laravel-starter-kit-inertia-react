<?php

declare(strict_types=1);

namespace App\Services;

use App\Actions\ApplyWeighmentPenaltiesAction;
use App\Actions\UpdateStockLedger;
use App\Models\Rake;
use App\Models\RakeWagonWeighment;
use App\Models\RakeWeighment;
use App\Models\Wagon;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use InvalidArgumentException;
use Throwable;

final readonly class RakeWeighmentPdfImporter
{
    public function __construct(
        private WeighmentPdfImporter $importer,
        private RakeWeighmentXlsxParser $xlsxParser,
        private ApplyWeighmentPenaltiesAction $applyWeighmentPenalties,
        private UpdateStockLedger $updateStockLedger,
    ) {}

    /**
     * Import a weighment PDF for an existing rake. Creates wagons from the PDF when the rake has none,
     * then writes rake_weighments and rake_wagon_weighments.
     */
    public function importForRake(Rake $rake, UploadedFile $pdf, int $userId): RakeWeighment
    {
        Log::info('RakeWeighmentPdfImporter: starting', [
            'rake_id' => $rake->id,
            'user_id' => $userId,
        ]);

        $existing = $this->resolveExistingWeighmentForImport($rake);

        $parsed = $this->importer->parsePdfForRake($rake, $pdf);
        $storedPath = $pdf->store('weighment-pdfs', 'public');

        try {
            if ($existing !== null) {
                return $this->mergeDocumentIntoManualWeighment(
                    $rake,
                    $existing,
                    $parsed['header'],
                    $parsed['totals'],
                    $parsed['wagon_rows'],
                    $storedPath,
                    $userId,
                );
            }

            return $this->importForRakeFromParsed(
                $rake,
                $parsed['header'],
                $parsed['totals'],
                $parsed['wagon_rows'],
                $storedPath,
                $userId,
            );
        } catch (Throwable $e) {
            Log::error('RakeWeighmentPdfImporter: transaction failed', [
                'rake_id' => $rake->id,
                'exception' => get_class($e),
                'message' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    public function importForRakeFromXlsx(Rake $rake, UploadedFile $xlsx, int $userId): RakeWeighment
    {
        Log::info('RakeWeighmentXlsxImporter: starting', [
            'rake_id' => $rake->id,
            'user_id' => $userId,
        ]);

        $existing = $this->resolveExistingWeighmentForImport($rake);

        $realPath = $xlsx->getRealPath();
        if ($realPath === false || $realPath === '' || ! is_readable($realPath)) {
            throw new InvalidArgumentException('Unable to read uploaded XLSX file.');
        }

        $parsed = $this->xlsxParser->parseForRake($rake, $realPath);
        $storedPath = $xlsx->store('weighment-excels', 'public');

        try {
            if ($existing !== null) {
                return $this->mergeDocumentIntoManualWeighment(
                    $rake,
                    $existing,
                    $parsed['header'],
                    $parsed['totals'],
                    $parsed['wagon_rows'],
                    $storedPath,
                    $userId,
                );
            }

            return $this->importForRakeFromParsed(
                $rake,
                $parsed['header'],
                $parsed['totals'],
                $parsed['wagon_rows'],
                $storedPath,
                $userId,
            );
        } catch (Throwable $e) {
            Log::error('RakeWeighmentXlsxImporter: transaction failed', [
                'rake_id' => $rake->id,
                'exception' => get_class($e),
                'message' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    /**
     * Copy wagon identity and slip weights from rake_wagon_weighments onto wagons rows.
     * Placeholder wagons (e.g. W1, W2) matched by sequence keep stale DB values until this runs.
     */
    public function syncWagonsFromRakeWeighment(RakeWeighment $weighment): void
    {
        $rows = RakeWagonWeighment::query()
            ->where('rake_weighment_id', $weighment->id)
            ->whereNotNull('wagon_id')
            ->get();

        foreach ($rows as $ww) {
            $number = mb_trim((string) ($ww->wagon_number ?? ''));
            if ($number === '') {
                continue;
            }

            /** @var Wagon|null $wagon */
            $wagon = Wagon::query()->find($ww->wagon_id);
            if ($wagon === null) {
                continue;
            }

            $tare = null;
            if ($ww->printed_tare_mt !== null) {
                $tare = $ww->printed_tare_mt;
            } elseif ($ww->actual_tare_mt !== null) {
                $tare = $ww->actual_tare_mt;
            }

            $data = [
                'wagon_number' => $number,
                'wagon_sequence' => $ww->wagon_sequence !== null
                    ? (int) $ww->wagon_sequence
                    : $wagon->wagon_sequence,
            ];

            if ($ww->wagon_type !== null && mb_trim((string) $ww->wagon_type) !== '') {
                $data['wagon_type'] = mb_trim((string) $ww->wagon_type);
            }

            if ($tare !== null) {
                $data['tare_weight_mt'] = $tare;
            }

            if ($ww->cc_capacity_mt !== null) {
                $data['pcc_weight_mt'] = $ww->cc_capacity_mt;
            }

            $wagon->update($data);
        }
    }

    /**
     * @param  array<string, mixed>  $header
     * @param  array<string, float|null>  $totals
     * @param  array<int, array<string, mixed>>  $wagonRows
     */
    public function importForRakeFromParsed(
        Rake $rake,
        array $header,
        array $totals,
        array $wagonRows,
        string $storedPath,
        int $userId,
        ?RakeWeighment $mergeInto = null,
    ): RakeWeighment {
        $attemptNo = 1;
        $isMerge = $mergeInto !== null;

        return DB::transaction(function () use ($rake, $header, $totals, $wagonRows, $storedPath, $userId, $attemptNo, $mergeInto, $isMerge): RakeWeighment {
            $rake->load(['wagons' => fn ($q) => $q->orderBy('wagon_sequence')]);
            $rake->loadMissing('siding');

            $mergeOldNetMt = 0.0;
            if ($isMerge && $mergeInto !== null) {
                $mergeOldNetMt = $mergeInto->total_net_weight_mt !== null
                    ? round((float) $mergeInto->total_net_weight_mt, 2)
                    : 0.0;
            }

            $fallbackDate = null;
            $dateString = $header['loading_date'] ?? null;
            if (is_string($dateString) && mb_trim($dateString) !== '') {
                try {
                    $fallbackDate = Date::parse($dateString);
                } catch (Throwable) {
                    $fallbackDate = null;
                }
            }
            if ($fallbackDate === null && $rake->loading_date !== null) {
                $fallbackDate = Date::parse($rake->loading_date->toDateString());
            }

            $fromStation = $header['from_station'] ?? null;
            if (! is_string($fromStation) || mb_trim($fromStation) === '') {
                $fromStation = $rake->siding?->station_code ?? $rake->siding?->code ?? null;
            }

            $toStation = $header['to_station'] ?? null;
            if (! is_string($toStation) || mb_trim($toStation) === '') {
                $toStation = $rake->destination_code ?? null;
            }

            $priorityNumber = $header['priority_number'] ?? null;
            if (! is_string($priorityNumber) || mb_trim($priorityNumber) === '') {
                $priorityNumber = $rake->priority_number !== null ? (string) $rake->priority_number : null;
            }

            if ($rake->wagons->isEmpty()) {
                $this->createWagonsFromParsedRows($rake, $wagonRows);
                $rake->load(['wagons' => fn ($q) => $q->orderBy('wagon_sequence')]);
            }

            $wagons = $rake->wagons;

            $weighmentAttributes = [
                'rake_id' => $rake->id,
                'attempt_no' => $attemptNo,
                'gross_weighment_datetime' => $header['gross_weighment_datetime'] ?? $fallbackDate,
                'tare_weighment_datetime' => $header['tare_weighment_datetime'] ?? $fallbackDate,
                'train_name' => $header['train_name'] ?? null,
                'direction' => $header['direction'] ?? null,
                'commodity' => $header['commodity'] ?? null,
                'from_station' => $fromStation,
                'to_station' => $toStation,
                'priority_number' => $priorityNumber,
                'total_gross_weight_mt' => $totals['total_gross_weight_mt'] ?? null,
                'total_tare_weight_mt' => $totals['total_tare_weight_mt'] ?? null,
                'total_net_weight_mt' => $totals['total_net_weight_mt'] ?? null,
                'total_cc_weight_mt' => $totals['total_cc_weight_mt'] ?? null,
                'total_under_load_mt' => $totals['total_under_load_mt'] ?? null,
                'total_over_load_mt' => $totals['total_over_load_mt'] ?? null,
                'maximum_train_speed_kmph' => $totals['maximum_train_speed_kmph'] ?? null,
                'maximum_weight_mt' => $totals['maximum_weight_mt'] ?? null,
                'pdf_file_path' => $storedPath,
                'status' => 'success',
            ];

            if ($isMerge) {
                $mergeInto->update($weighmentAttributes);
                $weighment = $mergeInto->fresh();
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

            $matched = 0;
            foreach ($wagonRows as $row) {
                $seq = (int) ($row['sequence'] ?? 0);
                $wagonNumber = mb_trim((string) ($row['wagon_number'] ?? ''));
                $wagon = $wagonsBySequence[$seq] ?? $wagonsByNumber[$wagonNumber] ?? null;

                RakeWagonWeighment::query()->create([
                    'rake_weighment_id' => $weighment->id,
                    'wagon_id' => $wagon?->id,
                    'wagon_number' => $wagonNumber !== '' ? $wagonNumber : $wagon?->wagon_number,
                    'wagon_sequence' => $seq ?: $wagon?->wagon_sequence,
                    'wagon_type' => $row['wagon_type'] ?? null,
                    'axles' => $row['axles'] ?? null,
                    'cc_capacity_mt' => $row['cc_capacity_mt'] ?? null,
                    'printed_tare_mt' => $row['printed_tare_mt'] ?? null,
                    'actual_gross_mt' => $row['actual_gross_mt'] ?? null,
                    'actual_tare_mt' => $row['tare_weight_mt'] ?? null,
                    'net_weight_mt' => $row['net_weight_mt'] ?? null,
                    'under_load_mt' => $row['under_load_mt'] ?? null,
                    'over_load_mt' => $row['over_load_mt'] ?? null,
                    'speed_kmph' => $row['speed_kmph'] ?? null,
                ]);
                $matched++;
            }

            $this->syncWagonsFromRakeWeighment($weighment);

            Log::info('RakeWeighmentImporter: created weighment and wagon rows', [
                'rake_weighment_id' => $weighment->id,
                'matched' => $matched,
                'total_rows' => count($wagonRows),
                'merge' => $isMerge,
            ]);

            $rake->update([
                'data_source' => 'system',
                'loaded_weight_mt' => $weighment->total_net_weight_mt,
                'under_load_mt' => $weighment->total_under_load_mt,
                'over_load_mt' => $weighment->total_over_load_mt,
            ]);

            $siding = $rake->siding;
            $quantity = $weighment->total_net_weight_mt !== null ? (float) $weighment->total_net_weight_mt : 0.0;

            if (! $isMerge && $siding !== null && $quantity > 0) {
                $this->updateStockLedger->recordDispatch(
                    $siding,
                    $quantity,
                    $rake->id,
                    'Rake weighment imported',
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
                        'Rake weighment document merged',
                    );
                }
            }

            $this->applyWeighmentPenalties->handle($rake, $weighment);

            return $weighment->fresh(['rakeWagonWeighments.wagon']);
        });
    }

    /**
     * When a full document import already exists, reject. When a manual placeholder exists (no wagon rows), return it for merge.
     */
    private function resolveExistingWeighmentForImport(Rake $rake): ?RakeWeighment
    {
        $existing = $rake->rakeWeighments()
            ->withCount('rakeWagonWeighments')
            ->first();

        if ($existing === null) {
            return null;
        }

        if ($existing->rake_wagon_weighments_count > 0) {
            throw new InvalidArgumentException('A weighment has already been uploaded for this rake.');
        }

        return $existing;
    }

    /**
     * @param  array<string, mixed>  $header
     * @param  array<string, float|null>  $totals
     * @param  array<int, array<string, mixed>>  $wagonRows
     */
    private function mergeDocumentIntoManualWeighment(
        Rake $rake,
        RakeWeighment $manualWeighment,
        array $header,
        array $totals,
        array $wagonRows,
        string $storedPath,
        int $userId,
    ): RakeWeighment {
        $parsedNet = $totals['total_net_weight_mt'] ?? null;
        $manualNet = $manualWeighment->total_net_weight_mt;

        if ($parsedNet === null || $manualNet === null) {
            throw new InvalidArgumentException(
                'The document does not include a total net weight, or the manual weighment has no net weight to compare.',
            );
        }

        return $this->importForRakeFromParsed(
            $rake,
            $header,
            $totals,
            $wagonRows,
            $storedPath,
            $userId,
            $manualWeighment,
        );
    }

    /**
     * @param  array<int, array<string, mixed>>  $wagonRows
     */
    private function createWagonsFromParsedRows(Rake $rake, array $wagonRows): void
    {
        if ($wagonRows === []) {
            throw new InvalidArgumentException('Weighment PDF has no wagon rows to create wagons from.');
        }

        $seen = [];
        $autoSeq = 0;

        foreach ($wagonRows as $row) {
            $wagonNumber = mb_trim((string) ($row['wagon_number'] ?? ''));

            if ($wagonNumber === '') {
                throw new InvalidArgumentException('Weighment PDF contains a wagon row with an empty wagon number.');
            }

            if (mb_strlen($wagonNumber) < 5) {
                throw new InvalidArgumentException(
                    sprintf('Weighment PDF wagon number "%s" is too short (minimum 5 characters).', $wagonNumber),
                );
            }

            $seq = (int) ($row['sequence'] ?? 0);
            if ($seq <= 0) {
                $autoSeq++;
                $seq = $autoSeq;
            }

            $dedupeKey = $seq.'|'.$wagonNumber;
            if (isset($seen[$dedupeKey])) {
                continue;
            }
            $seen[$dedupeKey] = true;

            $printedTare = $row['printed_tare_mt'] ?? null;
            $tareCol = $row['tare_weight_mt'] ?? null;
            $tareMt = null;
            if (is_numeric($printedTare)) {
                $tareMt = (float) $printedTare;
            } elseif (is_numeric($tareCol)) {
                $tareMt = (float) $tareCol;
            }

            $cc = $row['cc_capacity_mt'] ?? null;
            $pccMt = is_numeric($cc) ? (float) $cc : null;

            $wagonType = $row['wagon_type'] ?? null;
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
                'tare_weight_mt' => $tareMt,
                'pcc_weight_mt' => $pccMt,
                'is_unfit' => false,
                'state' => 'pending',
            ]);
        }

        $count = Wagon::query()->where('rake_id', $rake->id)->count();
        $rake->update(['wagon_count' => $count]);
    }
}
