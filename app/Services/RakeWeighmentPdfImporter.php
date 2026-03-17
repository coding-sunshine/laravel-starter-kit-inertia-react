<?php

declare(strict_types=1);

namespace App\Services;

use App\Actions\ApplyWeighmentPenaltiesAction;
use App\Models\Rake;
use App\Models\RakeWagonWeighment;
use App\Models\RakeWeighment;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use InvalidArgumentException;
use Throwable;

final readonly class RakeWeighmentPdfImporter
{
    public function __construct(
        private WeighmentPdfImporter $importer,
        private ApplyWeighmentPenaltiesAction $applyWeighmentPenalties,
    ) {}

    /**
     * Import a weighment PDF for an existing rake. Writes only to rake_weighments and rake_wagon_weighments.
     */
    public function importForRake(Rake $rake, UploadedFile $pdf, int $userId): RakeWeighment
    {
        Log::info('RakeWeighmentPdfImporter: starting', [
            'rake_id' => $rake->id,
            'user_id' => $userId,
        ]);

        $parsed = $this->importer->parsePdfForRake($pdf);
        $header = $parsed['header'];
        $totals = $parsed['totals'];
        $wagonRows = $parsed['wagon_rows'];
        $storedPath = $parsed['stored_path'];

        $rake->load(['wagons' => fn ($q) => $q->orderBy('wagon_sequence')]);
        $wagons = $rake->wagons;

        if ($wagons->isEmpty()) {
            throw new InvalidArgumentException('This rake has no wagons. Add wagons before uploading a weighment PDF.');
        }

        $attemptNo = (int) ($rake->rakeWeighments()->max('attempt_no') ?? 0) + 1;

        try {
            return DB::transaction(function () use ($rake, $header, $totals, $wagonRows, $storedPath, $userId, $attemptNo, $wagons): RakeWeighment {
                $weighment = RakeWeighment::query()->create([
                    'rake_id' => $rake->id,
                    'attempt_no' => $attemptNo,
                    'gross_weighment_datetime' => $header['gross_weighment_datetime'] ?? null,
                    'tare_weighment_datetime' => $header['tare_weighment_datetime'] ?? null,
                    'train_name' => $header['train_name'] ?? null,
                    'direction' => $header['direction'] ?? null,
                    'commodity' => $header['commodity'] ?? null,
                    'from_station' => $header['from_station'] ?? null,
                    'to_station' => $header['to_station'] ?? null,
                    'priority_number' => $header['priority_number'] ?? null,
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
                    'created_by' => $userId,
                ]);

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

                Log::info('RakeWeighmentPdfImporter: created weighment and wagon rows', [
                    'rake_weighment_id' => $weighment->id,
                    'matched' => $matched,
                    'total_rows' => count($wagonRows),
                ]);

                $rake->update([
                    'data_source' => 'system',
                    'loaded_weight_mt' => $weighment->total_net_weight_mt,
                    'under_load_mt' => $weighment->total_under_load_mt,
                    'over_load_mt' => $weighment->total_over_load_mt,
                ]);

                $this->applyWeighmentPenalties->handle($rake, $weighment);

                return $weighment->fresh(['rakeWagonWeighments.wagon']);
            });
        } catch (Throwable $e) {
            Log::error('RakeWeighmentPdfImporter: transaction failed', [
                'rake_id' => $rake->id,
                'exception' => get_class($e),
                'message' => $e->getMessage(),
            ]);
            throw $e;
        }
    }
}
