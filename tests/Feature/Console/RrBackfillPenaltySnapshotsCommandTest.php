<?php

declare(strict_types=1);

use App\Models\PenaltyType;
use App\Models\RrCharge;
use App\Models\RrDocument;
use App\Models\RrPenaltySnapshot;

beforeEach(function (): void {
    PenaltyType::factory()->create(['code' => 'DCLA']);
    PenaltyType::factory()->create(['code' => 'ENHC']);
});

it('inserts a penalty snapshot for an rr_charges row with a penalty code and no existing snapshot', function (): void {
    $doc = RrDocument::factory()->create();
    $charge = RrCharge::factory()->create([
        'rr_document_id' => $doc->id,
        'charge_code' => 'DCLA',
        'charge_name' => 'Detention Claim',
        'amount' => 9200.00,
    ]);

    $this->artisan('rr:backfill-penalty-snapshots')
        ->expectsOutputToContain('DCLA')
        ->expectsOutputToContain('Rows inserted: 1 | Rows skipped (existing): 0')
        ->assertExitCode(0);

    $snapshot = RrPenaltySnapshot::where('rr_document_id', $doc->id)->where('penalty_code', 'DCLA')->first();
    expect($snapshot)->not->toBeNull()
        ->and((float) $snapshot->amount)->toBe(9200.00)
        ->and($snapshot->rake_id)->toBe($doc->rake_id)
        ->and($snapshot->rake_charge_id)->toBeNull()
        ->and($snapshot->meta)->toBe(['name' => 'Detention Claim']);
});

it('skips a charge when a penalty snapshot already exists for the same document and code', function (): void {
    $doc = RrDocument::factory()->create();
    RrCharge::factory()->create(['rr_document_id' => $doc->id, 'charge_code' => 'DCLA', 'amount' => 9200.00]);
    RrPenaltySnapshot::factory()->create(['rr_document_id' => $doc->id, 'penalty_code' => 'DCLA', 'amount' => 9200.00]);

    $this->artisan('rr:backfill-penalty-snapshots')
        ->expectsOutputToContain('Rows inserted: 0 | Rows skipped (existing): 1')
        ->assertExitCode(0);

    expect(RrPenaltySnapshot::where('rr_document_id', $doc->id)->where('penalty_code', 'DCLA')->count())->toBe(1);
});

it('ignores non-penalty charge codes', function (): void {
    $doc = RrDocument::factory()->create();
    RrCharge::factory()->create(['rr_document_id' => $doc->id, 'charge_code' => 'FREIGHT', 'amount' => 150000.00]);

    $this->artisan('rr:backfill-penalty-snapshots')
        ->expectsOutputToContain('Rows inserted: 0 | Rows skipped (existing): 0')
        ->assertExitCode(0);

    expect(RrPenaltySnapshot::where('rr_document_id', $doc->id)->count())->toBe(0);
});

it('dry-run reports what would be inserted without writing to the database', function (): void {
    $doc = RrDocument::factory()->create();
    RrCharge::factory()->create(['rr_document_id' => $doc->id, 'charge_code' => 'ENHC', 'amount' => 7800.00]);

    $this->artisan('rr:backfill-penalty-snapshots', ['--dry-run' => true])
        ->expectsOutputToContain('[DRY RUN] doc')
        ->assertExitCode(0);

    expect(RrPenaltySnapshot::where('rr_document_id', $doc->id)->count())->toBe(0);
});

it('is idempotent — a second run inserts nothing', function (): void {
    $doc = RrDocument::factory()->create();
    RrCharge::factory()->create(['rr_document_id' => $doc->id, 'charge_code' => 'DCLA', 'amount' => 9200.00]);

    $this->artisan('rr:backfill-penalty-snapshots')->assertExitCode(0);
    expect(RrPenaltySnapshot::where('rr_document_id', $doc->id)->count())->toBe(1);

    $this->artisan('rr:backfill-penalty-snapshots')
        ->expectsOutputToContain('Rows inserted: 0 | Rows skipped (existing): 1')
        ->assertExitCode(0);
    expect(RrPenaltySnapshot::where('rr_document_id', $doc->id)->count())->toBe(1);
});
