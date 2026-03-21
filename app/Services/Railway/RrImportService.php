<?php

declare(strict_types=1);

namespace App\Services\Railway;

use App\Http\Requests\StoreRrUploadRequest;
use App\Models\AppliedPenalty;
use App\Models\DiverrtDestination;
use App\Models\PenaltyType;
use App\Models\Rake;
use App\Models\RrCharge;
use App\Models\RrDocument;
use App\Models\RrPenaltySnapshot;
use App\Models\RrWagonSnapshot;
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
        return $this->importSnapshotOnly($parsed, $request, $request->validated(), null, null);
    }

    /**
     * Import RR with pre-validated data. Used when request validation is done elsewhere (e.g. upload with defaults).
     *
     * @param  array<string, mixed>  $validated  Must include siding_id, power_plant_id; request must contain 'pdf' file.
     */
    public function importWithValidated(array $parsed, Request $request, array $validated): RrDocument
    {
        $userId = $request->user()->id;

        try {
            return DB::transaction(function () use ($parsed, $validated, $request) {
                return $this->importSnapshotOnly($parsed, $request, $validated, null, null);
            });
        } catch (Throwable $e) {
            Log::error('RR import: transaction rolled back', [
                'error' => $e->getMessage(),
                'exception' => get_class($e),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString(),
            ]);
            throw $e;
        }
    }

    public function importSnapshotOnly(
        array $parsed,
        Request $request,
        array $validated,
        ?Rake $rake = null,
        ?DiverrtDestination $diverrtDestination = null,
    ): RrDocument {
        $userId = $request->user()->id;

        return DB::transaction(function () use ($parsed, $validated, $userId, $rake, $request, $diverrtDestination) {
            if ($diverrtDestination !== null && $rake === null) {
                throw new InvalidArgumentException('A rake is required when uploading a diversion Railway Receipt.');
            }

            if ($rake !== null) {
                $this->assertRrUploadSlotAvailable($rake, $diverrtDestination);
                $this->validateParsedRailwayReceiptAgainstRake($parsed, $rake, $diverrtDestination);
            }

            $this->validateNoDuplicates($parsed);

            $rrDate = $this->parseDate($parsed['rr_date'] ?? $parsed['rr_received_date'] ?? null);

            Log::debug('RR import (snapshot): creating document');
            $diverrtDestinationId = $diverrtDestination?->id;
            $rrDocument = $this->createSnapshotRrDocument($rake, $parsed, $validated, $userId, $rrDate, $diverrtDestinationId);

            $pdfFile = $validated['pdf'] ?? $request->file('pdf');
            if ($pdfFile) {
                Log::debug('RR import (snapshot): adding PDF media');
                $rrDocument->addMedia($pdfFile)->toMediaCollection('rr_pdf');
            } else {
                $rrDocument->addMediaFromRequest('pdf')->toMediaCollection('rr_pdf');
            }

            $wagonsToInsert = $parsed['wagons'] ?? [];
            Log::debug('RR import (snapshot): wagon snapshots to insert', ['rr_document_id' => $rrDocument->id, 'count' => count($wagonsToInsert)]);
            $this->insertWagonSnapshots($rrDocument, $rake, $wagonsToInsert);

            Log::debug('RR import (snapshot): inserting charges and penalty snapshots');
            $this->insertRrCharges($rrDocument, $parsed['charges'] ?? []);
            $this->insertPenaltySnapshots($rrDocument, $rake, $parsed['charges'] ?? []);

            return $rrDocument->fresh(['rrCharges', 'wagonSnapshots', 'penaltySnapshots']);
        });
    }

    private function assertRrUploadSlotAvailable(Rake $rake, ?DiverrtDestination $diverrtDestination): void
    {
        if ($diverrtDestination !== null) {
            if (! $rake->is_diverted) {
                throw new InvalidArgumentException('This rake is not marked as diverted; diversion Railway Receipt uploads are not allowed.');
            }
            if ((int) $diverrtDestination->rake_id !== (int) $rake->id) {
                throw new InvalidArgumentException('The selected diversion destination does not belong to this rake.');
            }
            $exists = RrDocument::query()
                ->where('rake_id', $rake->id)
                ->where('diverrt_destination_id', $diverrtDestination->id)
                ->exists();
            if ($exists) {
                throw new InvalidArgumentException('A Railway Receipt has already been uploaded for this diversion destination.');
            }

            return;
        }

        if ($rake->is_diverted) {
            $primaryTaken = RrDocument::query()
                ->where('rake_id', $rake->id)
                ->whereNull('diverrt_destination_id')
                ->exists();
            if ($primaryTaken) {
                throw new InvalidArgumentException('The primary Railway Receipt for this rake has already been uploaded.');
            }

            return;
        }

        if (RrDocument::query()->where('rake_id', $rake->id)->exists()) {
            throw new InvalidArgumentException('Railway Receipt has already been uploaded for this rake.');
        }
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

    /**
     * Rake-linked RR: enforce Station To vs rake.destination_code (primary) or diverrt_destination.location (diversion); Station From only for configured sidings (e.g. Dumka).
     *
     * @param  array<string, mixed>  $parsed
     */
    private function validateParsedRailwayReceiptAgainstRake(array $parsed, Rake $rake, ?DiverrtDestination $diverrtDestination = null): void
    {
        $rake->loadMissing('siding');
        $siding = $rake->siding;
        if ($siding === null) {
            throw new InvalidArgumentException('Rake has no siding assigned; cannot validate Railway Receipt.');
        }

        $strictStationCodes = array_map(
            static fn (string $c): string => mb_strtoupper(mb_trim($c)),
            config('rrmcs.rr_strict_from_station_station_codes', [])
        );
        $aliases = config('rrmcs.rr_from_station_pdf_code_aliases', []);
        if (! is_array($aliases)) {
            $aliases = [];
        }

        $sidingStationUpper = mb_strtoupper(mb_trim((string) $siding->station_code));
        if ($sidingStationUpper !== '' && in_array($sidingStationUpper, $strictStationCodes, true)) {
            $fromRaw = $parsed['from_station_code'] ?? null;
            if ($fromRaw === null || mb_trim((string) $fromRaw) === '') {
                throw new InvalidArgumentException('Railway Receipt PDF is missing Station From code (required for this siding).');
            }
            $fromNorm = mb_strtoupper(mb_trim((string) $fromRaw));
            if (isset($aliases[$fromNorm]) && is_string($aliases[$fromNorm])) {
                $fromNorm = mb_strtoupper(mb_trim($aliases[$fromNorm]));
            }
            $sidingCodeUpper = mb_strtoupper(mb_trim((string) $siding->code));
            if ($fromNorm !== $sidingStationUpper && $fromNorm !== $sidingCodeUpper) {
                throw new InvalidArgumentException(
                    sprintf(
                        'Railway Receipt Station From (%s) does not match this rake\'s siding (%s).',
                        mb_trim((string) $fromRaw),
                        $siding->name
                    )
                );
            }
        }

        $toRaw = $parsed['to_station_code'] ?? null;
        if ($toRaw === null || mb_trim((string) $toRaw) === '') {
            throw new InvalidArgumentException('Railway Receipt PDF is missing Station To code.');
        }
        $toNorm = mb_strtoupper(mb_trim((string) $toRaw));

        if ($diverrtDestination !== null) {
            $legLocation = $diverrtDestination->location;
            if ($legLocation === null || mb_trim((string) $legLocation) === '') {
                throw new InvalidArgumentException('Diversion destination has no location (station code) set; cannot validate Railway Receipt.');
            }
            if ($toNorm !== mb_strtoupper(mb_trim((string) $legLocation))) {
                throw new InvalidArgumentException(
                    sprintf(
                        'Railway Receipt Station To (%s) does not match this diversion leg (%s).',
                        mb_trim((string) $toRaw),
                        mb_trim((string) $legLocation)
                    )
                );
            }

            return;
        }

        $rakeDest = $rake->destination_code;
        if ($rakeDest === null || mb_trim((string) $rakeDest) === '') {
            throw new InvalidArgumentException('Set destination code on the rake before uploading the Railway Receipt.');
        }
        if ($toNorm !== mb_strtoupper(mb_trim((string) $rakeDest))) {
            throw new InvalidArgumentException(
                sprintf(
                    'Railway Receipt Station To (%s) does not match the rake destination code (%s).',
                    mb_trim((string) $toRaw),
                    mb_trim((string) $rakeDest)
                )
            );
        }
    }

    private function createSnapshotRrDocument(
        ?Rake $rake,
        array $parsed,
        array $validated,
        int $userId,
        \Carbon\CarbonInterface $rrDate,
        ?int $diverrtDestinationId = null,
    ): RrDocument {
        return RrDocument::query()->create([
            'rake_id' => $rake?->id,
            'diverrt_destination_id' => $diverrtDestinationId,
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
            'rate' => isset($parsed['rate']) ? (float) $parsed['rate'] : null,
            'class' => $parsed['class'] ?? null,
            'document_status' => 'parsed',
            'data_source' => $rake ? 'manual_rake_rr' : 'historical_rr_snapshot',
            'rr_details' => array_filter([
                'power_plant_id' => $validated['power_plant_id'] ?? null,
                'raw_text' => $parsed['raw_text'] ?? null,
                'wagon_count_rr' => $parsed['wagon_count'] ?? null,
                'total_weight_rr' => $parsed['total_weight'] ?? null,
            ]),
            'created_by' => $userId,
        ]);
    }

    /**
     * @param  array<int, array{sequence?: int, wagon_number: string, wagon_type?: string|null, pcc_weight?: float, loaded_weight?: float, permissible_weight?: float, overload_weight?: float}>  $wagons
     */
    private function insertWagonSnapshots(RrDocument $rrDocument, ?Rake $rake, array $wagons): void
    {
        $seenWagonNumbers = [];

        foreach ($wagons as $index => $w) {
            $wagonNumber = (string) ($w['wagon_number'] ?? '');
            if ($wagonNumber === '' || isset($seenWagonNumbers[$wagonNumber])) {
                continue;
            }
            $seenWagonNumbers[$wagonNumber] = true;

            $sequence = $w['sequence'] ?? $w['wagon_sequence'] ?? ($index + 1);

            RrWagonSnapshot::query()->create([
                'rr_document_id' => $rrDocument->id,
                'rake_id' => $rake?->id,
                'wagon_sequence' => (int) $sequence,
                'wagon_number' => $wagonNumber,
                'wagon_type' => $w['wagon_type'] ?? null,
                'pcc_weight_mt' => $this->clampWagonWeight($w['pcc_weight'] ?? $w['pcc_weight_mt'] ?? null),
                'loaded_weight_mt' => $this->clampWagonWeight($w['loaded_weight'] ?? $w['loaded_weight_mt'] ?? null),
                'permissible_weight_mt' => $this->clampWagonWeight($w['permissible_weight'] ?? $w['permissible_weight_mt'] ?? null),
                'overload_weight_mt' => $this->clampWagonWeight($w['overload_weight'] ?? $w['overload_weight_mt'] ?? null),
                'meta' => [],
            ]);
        }
    }

    /**
     * @param  array<int, array{code?: string, charge_code?: string, name?: string|null, amount?: float}>  $charges
     */
    private function insertPenaltySnapshots(RrDocument $rrDocument, ?Rake $rake, array $charges): void
    {
        foreach ($charges as $c) {
            $code = $c['code'] ?? $c['charge_code'] ?? '';
            $amount = (float) ($c['amount'] ?? 0);

            if ($code === '' || $amount <= 0) {
                continue;
            }

            RrPenaltySnapshot::query()->create([
                'rr_document_id' => $rrDocument->id,
                'rake_id' => $rake?->id,
                'penalty_code' => $code,
                'amount' => $amount,
                'meta' => [
                    'name' => $c['name'] ?? null,
                ],
            ]);
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
            'rate' => isset($parsed['rate']) ? (float) $parsed['rate'] : null,
            'class' => $parsed['class'] ?? null,
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
     * Create applied_penalties from RR charges. For POL1 (wagon-level), create one
     * record per overloaded wagon with proportional amount. For rake-level types
     * (POLA, DEM, etc.), create a single record.
     *
     * @param  array<int, array{code: string, name?: string|null, amount: float}>  $charges
     */
    private function applyPenaltiesFromCharges(Rake $rake, array $charges): void
    {
        $overloadedWagons = $rake->wagons()
            ->where('overload_weight_mt', '>', 0)
            ->orderBy('wagon_sequence')
            ->get();

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

            if (mb_strtoupper($code) === 'POL1' && $overloadedWagons->isNotEmpty()) {
                $totalOverload = (float) $overloadedWagons->sum('overload_weight_mt');
                if ($totalOverload > 0) {
                    $allocated = 0.0;
                    $last = $overloadedWagons->last();
                    foreach ($overloadedWagons as $wagon) {
                        $proportion = (float) $wagon->overload_weight_mt / $totalOverload;
                        $wagonAmount = $wagon->id === $last?->id
                            ? round($amount - $allocated, 2)
                            : round($amount * $proportion, 2);
                        $allocated += $wagonAmount;

                        AppliedPenalty::query()->create([
                            'penalty_type_id' => $penaltyType->id,
                            'rake_id' => $rake->id,
                            'wagon_id' => $wagon->id,
                            'quantity' => $wagon->overload_weight_mt,
                            'amount' => $wagonAmount,
                            'meta' => [
                                'source' => 'rr_charge',
                                'overload_weight_mt' => (float) $wagon->overload_weight_mt,
                            ],
                        ]);
                    }

                    continue;
                }
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
