<?php

declare(strict_types=1);

use App\Actions\RunReportAction;
use App\Models\Rake;
use App\Models\RakeCharge;
use App\Models\RrDocument;
use App\Models\Siding;

it('shows FAOC rebates and subtracts them from total freight in the RR charges report', function (): void {
    $siding = Siding::factory()->create();
    $rake = Rake::factory()->create(['siding_id' => $siding->id]);
    RrDocument::factory()->create(['rake_id' => $rake->id, 'diverrt_destination_id' => null]);

    $charge = fn (string $type, float $amount) => RakeCharge::factory()->create([
        'rake_id' => $rake->id,
        'diverrt_destination_id' => null,
        'is_actual_charges' => true,
        'charge_type' => $type,
        'amount' => $amount,
    ]);

    $charge('FREIGHT', 100000);
    $charge('OTHER_CHARGE', 5000);
    $charge('GST', 5250);
    $charge('REBATE', 25324);

    $rows = (new RunReportAction)->handle('rr_charges_report', [$siding->id], ['no_limit' => true]);

    expect($rows)->toHaveCount(1)
        ->and($rows[0]['Rebate'])->toBe(25324.0)
        ->and($rows[0]['Total Freight'])->toBe(84926.0);
});

it('subtracts rebates stored with a negative sign exactly once', function (): void {
    $siding = Siding::factory()->create();
    $rake = Rake::factory()->create(['siding_id' => $siding->id]);
    RrDocument::factory()->create(['rake_id' => $rake->id, 'diverrt_destination_id' => null]);

    RakeCharge::factory()->create([
        'rake_id' => $rake->id,
        'diverrt_destination_id' => null,
        'is_actual_charges' => true,
        'charge_type' => 'FREIGHT',
        'amount' => 100000,
    ]);

    RakeCharge::factory()->create([
        'rake_id' => $rake->id,
        'diverrt_destination_id' => null,
        'is_actual_charges' => true,
        'charge_type' => 'REBATE',
        'amount' => -1000,
    ]);

    $rows = (new RunReportAction)->handle('rr_charges_report', [$siding->id], ['no_limit' => true]);

    expect($rows[0]['Rebate'])->toBe(1000.0)
        ->and($rows[0]['Total Freight'])->toBe(99000.0);
});
