<?php

declare(strict_types=1);

use App\Events\RrPenaltySnapshotsImported;
use App\Models\PenaltyType;
use App\Models\Rake;
use App\Models\RrDocument;
use App\Services\Railway\RrImportService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;

it('emits RrPenaltySnapshotsImported once per rake after RR penalty snapshots are written', function (): void {
    Event::fake([RrPenaltySnapshotsImported::class]);

    PenaltyType::factory()->create(['code' => 'DEM', 'is_active' => true]);

    $rake = Rake::factory()->create();
    $rrDocument = RrDocument::query()->create([
        'rake_id' => $rake->id,
        'rr_number' => 'RR-EVT-'.uniqid(),
        'rr_received_date' => now(),
        'rr_weight_mt' => 0,
        'distance_km' => 0,
        'freight_total' => 0,
        'document_status' => 'parsed',
        'data_source' => 'manual_rake_rr',
    ]);

    $charges = [
        ['code' => 'DEM', 'name' => 'Demurrage', 'amount' => 1000.00],
    ];

    // The only place rr_penalty_snapshots are written from RrImportService is the
    // private insertPenaltySnapshots method. Drive it directly through the transaction
    // boundary so DB::afterCommit fires, without needing a full PDF upload pipeline.
    DB::transaction(function () use ($rrDocument, $rake, $charges): void {
        $service = resolve(RrImportService::class);
        $reflection = new ReflectionClass($service);
        $method = $reflection->getMethod('insertPenaltySnapshots');
        $method->setAccessible(true);
        $method->invoke($service, $rrDocument, $rake, $charges);
    });

    Event::assertDispatched(RrPenaltySnapshotsImported::class, fn ($e): bool => $e->rake->is($rake));
    Event::assertDispatchedTimes(RrPenaltySnapshotsImported::class, 1);
});

it('does not emit RrPenaltySnapshotsImported when no penalty rows are written', function (): void {
    Event::fake([RrPenaltySnapshotsImported::class]);

    $rake = Rake::factory()->create();
    $rrDocument = RrDocument::query()->create([
        'rake_id' => $rake->id,
        'rr_number' => 'RR-NOEVT-'.uniqid(),
        'rr_received_date' => now(),
        'rr_weight_mt' => 0,
        'distance_km' => 0,
        'freight_total' => 0,
        'document_status' => 'parsed',
        'data_source' => 'manual_rake_rr',
    ]);

    DB::transaction(function () use ($rrDocument, $rake): void {
        $service = resolve(RrImportService::class);
        $reflection = new ReflectionClass($service);
        $method = $reflection->getMethod('insertPenaltySnapshots');
        $method->setAccessible(true);
        // Non-penalty charge → no snapshot rows written.
        $method->invoke($service, $rrDocument, $rake, [
            ['code' => 'FREIGHT', 'name' => 'Freight', 'amount' => 500.00],
        ]);
    });

    Event::assertNotDispatched(RrPenaltySnapshotsImported::class);
});

it('classifies FAOC as a rebate rather than an added other charge', function (): void {
    $service = resolve(RrImportService::class);

    expect($service->resolveChargeType('FAOC', 'Freight Adjustment (Overcharge)', 25324.0))->toBe('REBATE')
        ->and($service->resolveChargeType('FAUC', 'Freight Adjustment (Undercharge)', 1715.0))->toBe('PENALTY')
        ->and($service->resolveChargeType('OTC', 'Other Charges', 82300.0))->toBe('OTHER_CHARGE');
});
