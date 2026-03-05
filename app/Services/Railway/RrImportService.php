<?php

declare(strict_types=1);

namespace App\Services\Railway;

use App\Http\Requests\StoreRrUploadRequest;
use App\Models\AppliedPenalty;
use App\Models\PenaltyType;
use App\Models\Rake;
use App\Models\RrCharge;
use App\Models\RrDocument;
use App\Models\Wagon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use InvalidArgumentException;
use Throwable;

final readonly class RrImportService
{
    private const WAGON_WEIGHT_MAX = 999.99;

    public function import(array $parsed, StoreRrUploadRequest $request): RrDocument
    {
        return $this->importWithValidated($parsed, $request, $request->validated());
    }

    /**
     * Import RR with pre-validated data. Used when request validation is done elsewhere (e.g. upload with defaults).
     *
     * @param  array<string, mixed>  $validated  Must include siding_id, power_plant_id; request must contain 'pdf' file.
     */
    public function importWithValidated(array $parsed, Request $request, array $validated): RrDocument
    {
        $userId = $request->user()->id;

        return DB::transaction(function () use ($parsed, $validated, $userId) {
            $this->validateNoDuplicates($parsed);

            $rrDate = $this->parseDate($parsed['rr_date'] ?? $parsed['rr_received_date'] ?? null);

            $rake = $this->createRake($parsed, $validated, $rrDate, $userId);
            $rrDocument = $this->createRrDocument($rake, $parsed, $validated, $userId);

            $pdfFile = $validated['pdf'] ?? $request->file('pdf');
            if ($pdfFile) {
                $rrDocument->addMedia($pdfFile)->toMediaCollection('rr_pdf');
            } else {
                $rrDocument->addMediaFromRequest('pdf')->toMediaCollection('rr_pdf');
            }

            $wagonsToInsert = $parsed['wagons'] ?? [];
            Log::debug('RR import: wagons to insert', ['rake_id' => $rake->id, 'count' => count($wagonsToInsert)]);

            $this->insertWagons($rake, $wagonsToInsert);
            $this->insertRrCharges($rrDocument, $parsed['charges'] ?? []);
            $this->applyPenaltiesFromCharges($rake, $parsed['charges'] ?? []);

            $this->updateRakeSummary($rake, $parsed, $rrDate);

            return $rrDocument->fresh(['rrCharges', 'rake.wagons']);
        });
    }

    private function validateNoDuplicates(array $parsed): void
    {
        $rrNumber = $parsed['rr_number'] ?? null;
        if (! $rrNumber) {
            throw new InvalidArgumentException('RR number is required.');
        }

        if (RrDocument::query()->where('rr_number', $rrNumber)->exists()) {
            throw new InvalidArgumentException("Railway Receipt number '{$rrNumber}' already exists.");
        }

    }

    private function parseDate(mixed $date): \Carbon\CarbonInterface
    {
        if (empty($date)) {
            return now();
        }
        if ($date instanceof \Carbon\CarbonInterface) {
            return $date;
        }

        try {
            return \Illuminate\Support\Facades\Date::parse($date);
        } catch (Throwable) {
            return now();
        }
    }

    private function createRake(array $parsed, array $validated, \Carbon\CarbonInterface $rrDate, int $userId): Rake
    {
        $wagonCount = (int) ($parsed['wagon_count'] ?? 0);
        $totalWeight = (float) ($parsed['total_weight'] ?? 0);
        $wagons = $parsed['wagons'] ?? [];
        if ($wagonCount === 0 && ! empty($wagons)) {
            $wagonCount = count($wagons);
        }
        if ($totalWeight === 0.0 && ! empty($wagons)) {
            $totalWeight = (float) array_sum(array_map(fn (array $w): float => (float) ($w['loaded_weight'] ?? $w['loaded_weight_mt'] ?? 0), $wagons));
        }

        $base = (string) ($parsed['rr_number'] ?? now()->timestamp);
        $suffix = mb_substr((string) now()->timestamp, -6);
        $rakeNumber = mb_substr('RR-'.mb_substr($base, 0, 10).'-'.$suffix, 0, 20);

        return Rake::query()->create([
            'siding_id' => $validated['siding_id'],
            'rake_number' => $rakeNumber,
            'wagon_count' => $wagonCount,
            'loaded_weight_mt' => $totalWeight,
            'rr_actual_date' => $rrDate,
            'state' => 'completed',
            'data_source' => 'historical_rr',
            'created_by' => $userId,
        ]);
    }

    private function createRrDocument(Rake $rake, array $parsed, array $validated, int $userId): RrDocument
    {
        $rrDate = $this->parseDate($parsed['rr_date'] ?? $parsed['rr_received_date'] ?? null);

        return RrDocument::query()->create([
            'rake_id' => $rake->id,
            'rr_number' => $parsed['rr_number'],
            'fnr' => $parsed['fnr'] ?? null,
            'rr_received_date' => $rrDate,
            'rr_weight_mt' => (float) ($parsed['total_weight'] ?? $parsed['rr_weight_mt'] ?? 0),
            'from_station_code' => $parsed['from_station_code'] ?? null,
            'to_station_code' => $parsed['to_station_code'] ?? null,
            'distance_km' => (float) ($parsed['distance_km'] ?? 0),
            'freight_total' => (float) ($parsed['freight_total'] ?? 0),
            'commodity_code' => $parsed['commodity_code'] ?? null,
            'commodity_description' => $parsed['commodity_description'] ?? null,
            'invoice_number' => $parsed['invoice_number'] ?? null,
            'invoice_date' => $parsed['invoice_date'] ?? null,
            'document_status' => 'parsed',
            'data_source' => 'historical_rr',
            'rr_details' => array_filter([
                'power_plant_id' => $validated['power_plant_id'] ?? null,
                'raw_text' => $parsed['raw_text'] ?? null,
            ]),
            'created_by' => $userId,
        ]);
    }

    /**
     * @param  array<int, array{sequence?: int, wagon_number: string, wagon_type?: string|null, pcc_weight?: float, loaded_weight?: float, permissible_weight?: float, overload_weight?: float}>  $wagons
     */
    private function insertWagons(Rake $rake, array $wagons): void
    {
        $seenWagonNumbers = [];
        $inserted = 0;

        foreach ($wagons as $index => $w) {
            $wagonNumber = (string) ($w['wagon_number'] ?? '');
            if ($wagonNumber === '' || isset($seenWagonNumbers[$wagonNumber])) {
                continue;
            }
            $seenWagonNumbers[$wagonNumber] = true;

            $sequence = $w['sequence'] ?? $w['wagon_sequence'] ?? ($index + 1);
            $pcc = $this->clampWagonWeight($w['pcc_weight'] ?? $w['pcc_weight_mt'] ?? null);
            $loaded = $this->clampWagonWeight($w['loaded_weight'] ?? $w['loaded_weight_mt'] ?? $pcc);
            $permissible = $this->clampWagonWeight($w['permissible_weight'] ?? $w['permissible_weight_mt'] ?? null);
            $overload = $this->clampWagonWeight($w['overload_weight'] ?? $w['overload_weight_mt'] ?? null);

            try {
                Wagon::query()->create([
                    'rake_id' => $rake->id,
                    'wagon_sequence' => (int) $sequence,
                    'wagon_number' => $wagonNumber,
                    'wagon_type' => $w['wagon_type'] ?? null,
                    'pcc_weight_mt' => $pcc,
                    'loaded_weight_mt' => $loaded,
                    'permissible_weight_mt' => $permissible,
                    'overload_weight_mt' => $overload,
                    'state' => 'completed',
                ]);
                $inserted++;
            } catch (Throwable $e) {
                Log::error('RR import: wagon insert failed', [
                    'rake_id' => $rake->id,
                    'wagon_number' => $wagonNumber,
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString(),
                ]);

                throw $e;
            }
        }

        Log::debug('RR import: wagons inserted', ['rake_id' => $rake->id, 'inserted' => $inserted]);
    }

    private function clampWagonWeight(mixed $value): ?float
    {
        if ($value === null || $value === '') {
            return null;
        }
        $f = (float) $value;
        if ($f < 0 || $f > self::WAGON_WEIGHT_MAX) {
            return null;
        }

        return round($f, 2);
    }

    /**
     * @param  array<int, array{code: string, name?: string|null, amount: float}>  $charges
     */
    private function insertRrCharges(RrDocument $rrDocument, array $charges): void
    {
        foreach ($charges as $c) {
            $code = $c['code'] ?? $c['charge_code'] ?? '';
            $name = $c['name'] ?? $c['charge_name'] ?? null;
            $amount = (float) ($c['amount'] ?? 0);

            RrCharge::query()->create([
                'rr_document_id' => $rrDocument->id,
                'charge_code' => $code,
                'charge_name' => $name,
                'amount' => $amount,
            ]);
        }
    }

    /**
     * @param  array<int, array{code: string, name?: string|null, amount: float}>  $charges
     */
    private function applyPenaltiesFromCharges(Rake $rake, array $charges): void
    {
        foreach ($charges as $c) {
            $code = $c['code'] ?? $c['charge_code'] ?? '';
            $amount = (float) ($c['amount'] ?? 0);

            if ($amount <= 0) {
                continue;
            }

            $penaltyType = PenaltyType::query()->where('code', mb_strtoupper($code))->first();
            if ($penaltyType === null) {
                continue;
            }

            AppliedPenalty::query()->create([
                'penalty_type_id' => $penaltyType->id,
                'rake_id' => $rake->id,
                'wagon_id' => null,
                'amount' => $amount,
                'meta' => ['source' => 'rr_charge'],
            ]);
        }
    }

    private function updateRakeSummary(Rake $rake, array $parsed, \Carbon\CarbonInterface $rrDate): void
    {
        $wagons = $parsed['wagons'] ?? [];
        $wagonCount = count($wagons) ?: (int) ($parsed['wagon_count'] ?? 0);
        $totalWeight = (float) ($parsed['total_weight'] ?? 0);
        if ($totalWeight === 0.0 && ! empty($wagons)) {
            $totalWeight = (float) array_sum(array_map(fn (array $w): float => (float) ($w['loaded_weight'] ?? $w['loaded_weight_mt'] ?? 0), $wagons));
        }
        if ($wagonCount === 0) {
            $wagonCount = $rake->wagons()->count();
        }
        if ($totalWeight === 0.0) {
            $totalWeight = (float) $rake->wagons()->sum('loaded_weight_mt');
        }

        $rake->update([
            'wagon_count' => $wagonCount,
            'loaded_weight_mt' => $totalWeight,
            'rr_actual_date' => $rrDate,
        ]);
    }
}
