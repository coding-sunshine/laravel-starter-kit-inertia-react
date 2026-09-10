<?php

declare(strict_types=1);

use App\Models\PenaltyType;
use App\Models\Rake;
use App\Models\RakeCharge;
use App\Models\RrCharge;
use App\Models\RrDocument;

it('classifies a DB-only penalty code as PENALTY via RrImportService, not the removed hardcoded list', function (): void {
    PenaltyType::factory()->create(['code' => 'FAUC']);

    $rake = Rake::factory()->create();
    $rrDocument = RrDocument::factory()->create(['rake_id' => $rake->id]);
    RrCharge::factory()->create([
        'rr_document_id' => $rrDocument->id,
        'rake_charge_id' => null,
        'charge_code' => 'FAUC',
        'charge_name' => 'Freight adjustment-Undercharge',
        'amount' => 500,
    ]);

    $this->artisan('rake-charges:backfill-links')->assertExitCode(0);

    $charge = RakeCharge::query()->where('rake_id', $rake->id)->where('charge_type', 'PENALTY')->first();

    expect($charge)->not->toBeNull()
        ->and((float) $charge->amount)->toBe(500.0);

    $rrCharge = RrCharge::query()->where('rr_document_id', $rrDocument->id)->first();
    expect($rrCharge->rake_charge_id)->toBe($charge->id);
});
