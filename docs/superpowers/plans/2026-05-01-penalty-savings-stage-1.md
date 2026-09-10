# Penalty Savings — Stage 1 Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Ship Stage 1 of the penalty savings program — predicted-vs-billed reconciliation per head, the missing PLO calculator, Pakur quick-placement capture, and a calibration corpus + CI gate.

**Architecture:** Add a single reconciliation table joining `rr_penalty_snapshots` (billed) and `applied_penalties` (predicted) per head. Reconciliation is fired by two new domain events (`RrPenaltySnapshotsImported`, `AppliedPenaltyPersisted`) and processed on the existing Horizon `penalties` queue. The PLO calculator follows the existing weighment-penalty Action pattern (compute-then-apply split). Pakur supervisors get a mobile-friendly Inertia page to capture rake placement timestamps. A calibration corpus of real RR-doc samples becomes a hard CI gate before merge.

**Tech Stack:** Laravel 13, Pest 4, PostgreSQL, Filament 5, Inertia React v3, Wayfinder, Horizon, wildside/userstamps. No Pennant flags. Direct cut-over per the umbrella spec.

**Source spec:** `docs/superpowers/specs/2026-05-01-penalty-savings-program-design.md`

**Depends on:** the penalty-fix spec (`docs/superpowers/specs/2026-04-29-penalty-fix-design.md`) being merged. That spec corrects `ApplyDemurragePenaltyAction` so it actually produces predicted demurrage rows that this stage can reconcile against.

---

## File Structure (created or modified)

**Created:**
- `database/migrations/2026_05_01_000001_create_penalty_reconciliations_table.php`
- `database/migrations/2026_05_01_000002_create_commodity_utilisation_thresholds_table.php`
- `database/seeders/CommodityUtilisationThresholdSeeder.php`
- `app/Models/PenaltyReconciliation.php`
- `app/Models/CommodityUtilisationThreshold.php`
- `database/factories/PenaltyReconciliationFactory.php`
- `database/factories/CommodityUtilisationThresholdFactory.php`
- `app/Actions/ReconciliationOutcome.php` (readonly DTO returned by reconciliation Action)
- `app/Actions/ReconcilePenaltyHeadsAction.php`
- `app/Actions/PloPenaltyResult.php` (readonly DTO returned by PLO calculator)
- `app/Actions/CalculatePloPenaltyAction.php`
- `app/Actions/ApplyPloPenaltyAction.php`
- `app/Events/RrPenaltySnapshotsImported.php`
- `app/Events/AppliedPenaltyPersisted.php`
- `app/Listeners/ReconcileOnRrImport.php`
- `app/Listeners/ReconcileOnAppliedPenalty.php`
- `app/Jobs/ReconcilePenaltyHeadsJob.php`
- `app/Filament/Resources/PenaltyReconciliationResource.php` (+ pages)
- `app/Filament/Resources/CommodityUtilisationThresholdResource.php` (+ pages)
- `app/Http/Controllers/Sidings/QuickPlacementController.php`
- `app/Http/Requests/Sidings/QuickPlacementRequest.php`
- `app/Console/Commands/PakurBackfillPlacementCommand.php`
- `resources/js/pages/sidings/quick-placement.tsx`
- `tests/Unit/Actions/ReconcilePenaltyHeadsActionTest.php`
- `tests/Unit/Actions/CalculatePloPenaltyActionTest.php`
- `tests/Feature/Actions/ApplyPloPenaltyActionTest.php`
- `tests/Feature/Sidings/QuickPlacementTest.php`
- `tests/Feature/Console/PakurBackfillPlacementCommandTest.php`
- `tests/Feature/Calibration/RrReconciliationCalibrationTest.php`
- `tests/Fixtures/RailwayBills/README.md`
- `tests/Fixtures/RailwayBills/2026-04-01-dumka-dem-sample-01.json`
- `docs/architecture/ADRs/ADR-002-penalty-savings-staged-rollout.md`
- `docs/developer/backend/actions/ReconcilePenaltyHeadsAction.md`
- `docs/developer/backend/actions/CalculatePloPenaltyAction.md`
- `docs/developer/backend/actions/ApplyPloPenaltyAction.md`
- `docs/user-guide/sidings/quick-placement.md`

**Modified:**
- `app/Actions/ApplyDemurragePenaltyAction.php` — emit `AppliedPenaltyPersisted` after persist
- `app/Actions/ApplyWeighmentPenaltiesAction.php` — emit `AppliedPenaltyPersisted` after persist
- `app/Services/Railway/RrImportService.php` — emit `RrPenaltySnapshotsImported` after batch upsert
- `app/Services/RakeWeighmentPdfImporter.php` — invoke `ApplyPloPenaltyAction` after weighment finalisation
- `app/Http/Controllers/Rakes/RakesController.php` — pass reconciliation prop to rake show view
- `resources/js/pages/rakes/show.tsx` (or equivalent) — render reconciliation row inline
- `routes/web.php` — register Pakur quick-placement routes
- `docs/architecture/ADRs/README.md` — add ADR-002 to index
- `docs/.manifest.json` — register new Action docs

---

## Task 1: ADR-002 — record three-stage decomposition decision

**Files:**
- Create: `docs/architecture/ADRs/ADR-002-penalty-savings-staged-rollout.md`
- Modify: `docs/architecture/ADRs/README.md`

- [ ] **Step 1: Create the ADR file**

Create `docs/architecture/ADRs/ADR-002-penalty-savings-staged-rollout.md`:

```markdown
# ADR-002 — Penalty savings program: staged rollout, no feature flags

**Status:** Accepted
**Date:** 2026-05-01

## Context

Historical RR-doc backfill shows a `~₹1.34 Cr` actual penalty pool already billed by Indian Railways. Distribution: 85% demurrage (DEM), 15% penal loading overcharge (PLO), <1% combined POL1/POLA/ENHC. Predictive coverage in code today inverts this — POL1/POLA are implemented, DEM only barely produces output, PLO not at all. `rr_penalty_snapshots` (billed) and `applied_penalties` (predicted) sit side-by-side with no reconciliation layer. Loadrite hardware is partially deployed (Dumka).

## Decision

Deliver penalty savings as a three-stage program documented in `docs/superpowers/specs/2026-05-01-penalty-savings-program-design.md`:

- Stage 1 — predicted-vs-billed reconciliation, PLO calculator, Pakur data-capture, calibration corpus
- Stage 2 — Loadrite live ingestion + WhatsApp alert channel
- Stage 3 — AI dispute factory

Each stage is independently shippable. Activation is data-driven (Loadrite settings row presence) and policy-gated (`DisputePolicy`). **No Pennant feature flags.** Calibration corpus is a hard CI gate at Stage-1 merge. Rollback = `git revert`; all migrations stay additive.

## Consequences

**Easier:**
- Each stage compounds value; Stage 1 alone moves the dial because demurrage (85%) and PLO (15%) become predicted and reconciled.
- No flag-machinery overhead. Single source of truth in code.

**Harder:**
- No instant flag-flip rollback. Mitigated by calibration gate, additive migrations, watch-window protocol, and compute-then-apply split that enables dry-run validation.
- Calibration corpus must exist before Stage-1 merge. Mitigated by fixture-collection task in this plan.

## References

- Umbrella spec: `docs/superpowers/specs/2026-05-01-penalty-savings-program-design.md`
- Stage-1 plan: `docs/superpowers/plans/2026-05-01-penalty-savings-stage-1.md`
- Penalty fix spec (prerequisite): `docs/superpowers/specs/2026-04-29-penalty-fix-design.md`
```

- [ ] **Step 2: Add ADR-002 to the index**

In `docs/architecture/ADRs/README.md`, find the index table and add the new row below the existing ADR-001 row:

```markdown
| [ADR-002](./ADR-002-penalty-savings-staged-rollout.md) | Penalty savings program: staged rollout, no feature flags | Accepted |
```

- [ ] **Step 3: Commit**

```bash
git add docs/architecture/ADRs/ADR-002-penalty-savings-staged-rollout.md docs/architecture/ADRs/README.md
git commit -m "docs(adr): record staged rollout decision for penalty savings program (ADR-002)"
```

---

## Task 2: Migration + Model + Factory — `penalty_reconciliations`

**Files:**
- Create: `database/migrations/2026_05_01_000001_create_penalty_reconciliations_table.php`
- Create: `app/Models/PenaltyReconciliation.php`
- Create: `database/factories/PenaltyReconciliationFactory.php`

- [ ] **Step 1: Generate the migration**

```bash
php artisan make:migration create_penalty_reconciliations_table --no-interaction
```

Note: Laravel will create the file with today's timestamp. Rename it to `2026_05_01_000001_create_penalty_reconciliations_table.php` so the order of migrations in this stage is deterministic.

- [ ] **Step 2: Fill in the migration**

```php
<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('penalty_reconciliations', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('rake_id')->constrained()->cascadeOnDelete();
            $table->string('penalty_code', 16);
            $table->decimal('predicted_amount', 12, 2)->nullable();
            $table->decimal('billed_amount', 12, 2)->nullable();
            $table->decimal('variance', 12, 2)->nullable();
            $table->decimal('variance_pct', 6, 2)->nullable();
            $table->boolean('dispute_candidate')->default(false);
            $table->json('notes')->nullable();
            $table->dateTime('reconciled_at');
            $table->timestamps();
            $table->userstamps();

            $table->unique(['rake_id', 'penalty_code'], 'penalty_reconciliations_rake_code_unique');
            $table->index(['penalty_code', 'dispute_candidate']);
            $table->index('reconciled_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('penalty_reconciliations');
    }
};
```

- [ ] **Step 3: Run the migration**

```bash
php artisan migrate --no-interaction
```

Expected: `Migrating: 2026_05_01_000001_create_penalty_reconciliations_table` then `Migrated`.

- [ ] **Step 4: Generate the model + factory**

```bash
php artisan make:model PenaltyReconciliation --factory --no-interaction
```

- [ ] **Step 5: Replace generated model contents**

`app/Models/PenaltyReconciliation.php`:

```php
<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Wildside\Userstamps\Userstamps;

final class PenaltyReconciliation extends Model
{
    use HasFactory;
    use Userstamps;

    protected $fillable = [
        'rake_id',
        'penalty_code',
        'predicted_amount',
        'billed_amount',
        'variance',
        'variance_pct',
        'dispute_candidate',
        'notes',
        'reconciled_at',
    ];

    protected $casts = [
        'predicted_amount' => 'decimal:2',
        'billed_amount' => 'decimal:2',
        'variance' => 'decimal:2',
        'variance_pct' => 'decimal:2',
        'dispute_candidate' => 'boolean',
        'notes' => 'array',
        'reconciled_at' => 'datetime',
    ];

    public function rake(): BelongsTo
    {
        return $this->belongsTo(Rake::class);
    }
}
```

- [ ] **Step 6: Replace generated factory contents**

`database/factories/PenaltyReconciliationFactory.php`:

```php
<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\PenaltyReconciliation;
use App\Models\Rake;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PenaltyReconciliation>
 */
final class PenaltyReconciliationFactory extends Factory
{
    protected $model = PenaltyReconciliation::class;

    public function definition(): array
    {
        $predicted = $this->faker->randomFloat(2, 1000, 50000);
        $billed = $this->faker->randomFloat(2, 1000, 50000);
        $variance = $billed - $predicted;
        $variancePct = $predicted > 0 ? ($variance / $predicted) * 100 : null;

        return [
            'rake_id' => Rake::factory(),
            'penalty_code' => $this->faker->randomElement(['DEM', 'PLO', 'POL1', 'POLA', 'ENHC']),
            'predicted_amount' => $predicted,
            'billed_amount' => $billed,
            'variance' => $variance,
            'variance_pct' => $variancePct,
            'dispute_candidate' => $variance > $predicted * 0.15,
            'notes' => null,
            'reconciled_at' => now(),
        ];
    }
}
```

- [ ] **Step 7: Run pint**

```bash
vendor/bin/pint --dirty --format agent
```

- [ ] **Step 8: Sanity-check the migration end-to-end**

```bash
php artisan tinker --execute 'App\Models\PenaltyReconciliation::factory()->create(); echo App\Models\PenaltyReconciliation::count();'
```

Expected output: `1`.

- [ ] **Step 9: Commit**

```bash
git add database/migrations/2026_05_01_000001_create_penalty_reconciliations_table.php app/Models/PenaltyReconciliation.php database/factories/PenaltyReconciliationFactory.php
git commit -m "feat(penalties): add penalty_reconciliations table, model, and factory"
```

---

## Task 3: Reconciliation outcome DTO

**Files:**
- Create: `app/Actions/ReconciliationOutcome.php`

- [ ] **Step 1: Create the DTO**

`app/Actions/ReconciliationOutcome.php`:

```php
<?php

declare(strict_types=1);

namespace App\Actions;

/**
 * Result struct returned by ReconcilePenaltyHeadsAction::handle().
 *
 * Pure data — no behaviour. Lives next to the Action so callers reading the
 * action don't have to chase types across the codebase.
 */
final readonly class ReconciliationOutcome
{
    /**
     * @param  list<int>  $createdIds        IDs of penalty_reconciliations created in this run
     * @param  list<int>  $updatedIds        IDs of penalty_reconciliations updated in this run
     * @param  list<int>  $disputeCandidateIds  IDs flagged as dispute candidates after reconciliation
     */
    public function __construct(
        public int $rakeId,
        public array $createdIds,
        public array $updatedIds,
        public array $disputeCandidateIds,
    ) {}

    public function totalAffected(): int
    {
        return count($this->createdIds) + count($this->updatedIds);
    }
}
```

- [ ] **Step 2: Run pint**

```bash
vendor/bin/pint --dirty --format agent
```

- [ ] **Step 3: Commit**

```bash
git add app/Actions/ReconciliationOutcome.php
git commit -m "feat(penalties): add ReconciliationOutcome DTO"
```

---

## Task 4: ReconcilePenaltyHeadsAction — TDD

This is the core of Stage 1. It joins `applied_penalties` (predicted) and `rr_penalty_snapshots` (billed) for a single rake into one row per `penalty_code`, and flags dispute candidates.

**Files:**
- Create: `app/Actions/ReconcilePenaltyHeadsAction.php`
- Create: `tests/Unit/Actions/ReconcilePenaltyHeadsActionTest.php`

- [ ] **Step 1: Write the failing test file**

```bash
php artisan make:test --pest --unit ReconcilePenaltyHeadsActionTest --no-interaction
```

Replace the generated file at `tests/Unit/Actions/ReconcilePenaltyHeadsActionTest.php` with:

```php
<?php

declare(strict_types=1);

use App\Actions\ReconcilePenaltyHeadsAction;
use App\Models\AppliedPenalty;
use App\Models\PenaltyReconciliation;
use App\Models\PenaltyType;
use App\Models\Rake;
use App\Models\RrPenaltySnapshot;

beforeEach(function (): void {
    $this->action = resolve(ReconcilePenaltyHeadsAction::class);
});

it('creates a reconciliation row when predicted and billed both exist for the same head', function (): void {
    $rake = Rake::factory()->create();
    $type = PenaltyType::query()->where('code', 'DEM')->firstOrFail();

    AppliedPenalty::factory()->for($rake)->for($type, 'penaltyType')->create([
        'amount' => 10000.00,
    ]);
    RrPenaltySnapshot::factory()->for($rake)->create([
        'penalty_code' => 'DEM',
        'amount' => 10500.00,
    ]);

    $outcome = $this->action->handle($rake);

    expect($outcome->totalAffected())->toBe(1);

    $row = PenaltyReconciliation::query()->sole();
    expect($row->penalty_code)->toBe('DEM')
        ->and((float) $row->predicted_amount)->toBe(10000.00)
        ->and((float) $row->billed_amount)->toBe(10500.00)
        ->and((float) $row->variance)->toBe(500.00)
        ->and((float) $row->variance_pct)->toBe(5.00)
        ->and($row->dispute_candidate)->toBeFalse();
});

it('flags dispute candidate when billed exceeds predicted by more than 15 percent', function (): void {
    $rake = Rake::factory()->create();
    $type = PenaltyType::query()->where('code', 'DEM')->firstOrFail();

    AppliedPenalty::factory()->for($rake)->for($type, 'penaltyType')->create(['amount' => 10000.00]);
    RrPenaltySnapshot::factory()->for($rake)->create([
        'penalty_code' => 'DEM',
        'amount' => 12000.00, // 20% over predicted
    ]);

    $this->action->handle($rake);

    $row = PenaltyReconciliation::query()->sole();
    expect($row->dispute_candidate)->toBeTrue();
});

it('flags dispute candidate when railway bills a head that was never predicted', function (): void {
    $rake = Rake::factory()->create();
    RrPenaltySnapshot::factory()->for($rake)->create([
        'penalty_code' => 'PLO',
        'amount' => 5000.00,
    ]);

    $this->action->handle($rake);

    $row = PenaltyReconciliation::query()->where('penalty_code', 'PLO')->sole();
    expect($row->predicted_amount)->toBeNull()
        ->and((float) $row->billed_amount)->toBe(5000.00)
        ->and($row->dispute_candidate)->toBeTrue();
});

it('records predicted-only rows without flagging them as dispute candidates', function (): void {
    $rake = Rake::factory()->create();
    $type = PenaltyType::query()->where('code', 'POL1')->firstOrFail();

    AppliedPenalty::factory()->for($rake)->for($type, 'penaltyType')->create(['amount' => 1500.00]);

    $this->action->handle($rake);

    $row = PenaltyReconciliation::query()->where('penalty_code', 'POL1')->sole();
    expect((float) $row->predicted_amount)->toBe(1500.00)
        ->and($row->billed_amount)->toBeNull()
        ->and($row->dispute_candidate)->toBeFalse();
});

it('is idempotent — running twice updates the same row instead of creating duplicates', function (): void {
    $rake = Rake::factory()->create();
    $type = PenaltyType::query()->where('code', 'DEM')->firstOrFail();
    AppliedPenalty::factory()->for($rake)->for($type, 'penaltyType')->create(['amount' => 1000.00]);

    $this->action->handle($rake);
    $this->action->handle($rake);

    expect(PenaltyReconciliation::query()->count())->toBe(1);
});

it('handles multiple heads on the same rake in one call', function (): void {
    $rake = Rake::factory()->create();
    $dem = PenaltyType::query()->where('code', 'DEM')->firstOrFail();
    $pol1 = PenaltyType::query()->where('code', 'POL1')->firstOrFail();

    AppliedPenalty::factory()->for($rake)->for($dem, 'penaltyType')->create(['amount' => 5000.00]);
    AppliedPenalty::factory()->for($rake)->for($pol1, 'penaltyType')->create(['amount' => 1500.00]);
    RrPenaltySnapshot::factory()->for($rake)->create(['penalty_code' => 'DEM', 'amount' => 5500.00]);
    RrPenaltySnapshot::factory()->for($rake)->create(['penalty_code' => 'POL1', 'amount' => 1500.00]);

    $outcome = $this->action->handle($rake);

    expect($outcome->totalAffected())->toBe(2)
        ->and(PenaltyReconciliation::query()->where('rake_id', $rake->id)->count())->toBe(2);
});
```

- [ ] **Step 2: Run the test to verify it fails**

```bash
php artisan test --compact --filter=ReconcilePenaltyHeadsActionTest
```

Expected: failures with "Class App\Actions\ReconcilePenaltyHeadsAction not found" or similar.

- [ ] **Step 3: Generate the action skeleton**

```bash
php artisan make:action ReconcilePenaltyHeadsAction --no-interaction
```

- [ ] **Step 4: Implement the action**

Replace `app/Actions/ReconcilePenaltyHeadsAction.php`:

```php
<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\AppliedPenalty;
use App\Models\PenaltyReconciliation;
use App\Models\Rake;
use App\Models\RrPenaltySnapshot;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Reconcile predicted (applied_penalties) and billed (rr_penalty_snapshots)
 * amounts per penalty head, for a single rake. Idempotent.
 */
final readonly class ReconcilePenaltyHeadsAction
{
    public function handle(Rake $rake): ReconciliationOutcome
    {
        return DB::transaction(function () use ($rake): ReconciliationOutcome {
            $predictedByCode = $this->aggregatePredicted($rake);
            $billedByCode = $this->aggregateBilled($rake);
            $codes = $predictedByCode->keys()->merge($billedByCode->keys())->unique();

            $created = [];
            $updated = [];
            $candidates = [];

            foreach ($codes as $code) {
                $predicted = $predictedByCode->get($code);
                $billed = $billedByCode->get($code);
                $variance = ($billed ?? 0.0) - ($predicted ?? 0.0);
                $variancePct = $predicted !== null && $predicted > 0
                    ? round(($variance / $predicted) * 100, 2)
                    : null;
                $disputeCandidate = $this->isDisputeCandidate($predicted, $billed);

                $existing = PenaltyReconciliation::query()
                    ->where('rake_id', $rake->id)
                    ->where('penalty_code', $code)
                    ->first();

                $row = PenaltyReconciliation::query()->updateOrCreate(
                    ['rake_id' => $rake->id, 'penalty_code' => $code],
                    [
                        'predicted_amount' => $predicted,
                        'billed_amount' => $billed,
                        'variance' => round($variance, 2),
                        'variance_pct' => $variancePct,
                        'dispute_candidate' => $disputeCandidate,
                        'reconciled_at' => now(),
                    ],
                );

                if ($existing === null) {
                    $created[] = $row->id;
                } else {
                    $updated[] = $row->id;
                }
                if ($disputeCandidate) {
                    $candidates[] = $row->id;
                }
            }

            return new ReconciliationOutcome(
                rakeId: $rake->id,
                createdIds: $created,
                updatedIds: $updated,
                disputeCandidateIds: $candidates,
            );
        });
    }

    /**
     * @return Collection<string, float>
     */
    private function aggregatePredicted(Rake $rake): Collection
    {
        return AppliedPenalty::query()
            ->join('penalty_types', 'penalty_types.id', '=', 'applied_penalties.penalty_type_id')
            ->where('applied_penalties.rake_id', $rake->id)
            ->groupBy('penalty_types.code')
            ->selectRaw('penalty_types.code as code, sum(applied_penalties.amount) as total')
            ->get()
            ->mapWithKeys(fn ($row): array => [$row->code => (float) $row->total]);
    }

    /**
     * @return Collection<string, float>
     */
    private function aggregateBilled(Rake $rake): Collection
    {
        return RrPenaltySnapshot::query()
            ->where('rake_id', $rake->id)
            ->groupBy('penalty_code')
            ->selectRaw('penalty_code, sum(amount) as total')
            ->get()
            ->mapWithKeys(fn ($row): array => [$row->penalty_code => (float) $row->total]);
    }

    private function isDisputeCandidate(?float $predicted, ?float $billed): bool
    {
        if ($billed === null || $billed <= 0) {
            return false;
        }
        if ($predicted === null) {
            return true;
        }
        return $billed > $predicted * 1.15;
    }
}
```

- [ ] **Step 5: Run the test again to verify it passes**

```bash
php artisan test --compact --filter=ReconcilePenaltyHeadsActionTest
```

Expected: 6 passing tests.

If any factories needed (e.g. `RrPenaltySnapshotFactory`) don't exist, generate them with `php artisan make:factory RrPenaltySnapshotFactory --no-interaction` and fill them in based on the columns observed in the migration `2026_03_19_000001_create_rr_penalty_snapshots_table.php`. The same applies to `AppliedPenaltyFactory` if absent — fill with sensible defaults using `for(Rake::factory())` and `for(PenaltyType::factory())`.

- [ ] **Step 6: Run pint**

```bash
vendor/bin/pint --dirty --format agent
```

- [ ] **Step 7: Commit**

```bash
git add app/Actions/ReconcilePenaltyHeadsAction.php tests/Unit/Actions/ReconcilePenaltyHeadsActionTest.php database/factories/RrPenaltySnapshotFactory.php database/factories/AppliedPenaltyFactory.php
git commit -m "feat(penalties): add ReconcilePenaltyHeadsAction with full TDD coverage"
```

---

## Task 5: Domain events for reconciliation

**Files:**
- Create: `app/Events/AppliedPenaltyPersisted.php`
- Create: `app/Events/RrPenaltySnapshotsImported.php`

- [ ] **Step 1: Generate the events**

```bash
php artisan make:event AppliedPenaltyPersisted --no-interaction
php artisan make:event RrPenaltySnapshotsImported --no-interaction
```

- [ ] **Step 2: Fill in `AppliedPenaltyPersisted`**

`app/Events/AppliedPenaltyPersisted.php`:

```php
<?php

declare(strict_types=1);

namespace App\Events;

use App\Models\Rake;
use Illuminate\Foundation\Events\Dispatchable;

final class AppliedPenaltyPersisted
{
    use Dispatchable;

    /**
     * Fired after one or more AppliedPenalty rows have been written/updated for a rake.
     * Source identifies which calculator path produced them (demurrage, weighment, plo).
     */
    public function __construct(
        public Rake $rake,
        public string $source,
    ) {}
}
```

- [ ] **Step 3: Fill in `RrPenaltySnapshotsImported`**

`app/Events/RrPenaltySnapshotsImported.php`:

```php
<?php

declare(strict_types=1);

namespace App\Events;

use App\Models\Rake;
use Illuminate\Foundation\Events\Dispatchable;

final class RrPenaltySnapshotsImported
{
    use Dispatchable;

    /**
     * Fired after rr_penalty_snapshots rows have been written for a rake from
     * an RR document import (live or historical).
     */
    public function __construct(public Rake $rake) {}
}
```

- [ ] **Step 4: Run pint**

```bash
vendor/bin/pint --dirty --format agent
```

- [ ] **Step 5: Commit**

```bash
git add app/Events/AppliedPenaltyPersisted.php app/Events/RrPenaltySnapshotsImported.php
git commit -m "feat(penalties): add domain events AppliedPenaltyPersisted and RrPenaltySnapshotsImported"
```

---

## Task 6: Reconciliation job + listeners

**Files:**
- Create: `app/Jobs/ReconcilePenaltyHeadsJob.php`
- Create: `app/Listeners/ReconcileOnAppliedPenalty.php`
- Create: `app/Listeners/ReconcileOnRrImport.php`

- [ ] **Step 1: Generate the job**

```bash
php artisan make:job ReconcilePenaltyHeadsJob --no-interaction
```

- [ ] **Step 2: Fill in the job**

`app/Jobs/ReconcilePenaltyHeadsJob.php`:

```php
<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Actions\ReconcilePenaltyHeadsAction;
use App\Models\Rake;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

final class ReconcilePenaltyHeadsJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public string $queue = 'penalties';

    public function __construct(public Rake $rake) {}

    public function handle(ReconcilePenaltyHeadsAction $action): void
    {
        $action->handle($this->rake);
    }
}
```

- [ ] **Step 3: Generate listeners**

```bash
php artisan make:listener ReconcileOnAppliedPenalty --event=AppliedPenaltyPersisted --no-interaction
php artisan make:listener ReconcileOnRrImport --event=RrPenaltySnapshotsImported --no-interaction
```

- [ ] **Step 4: Fill in `ReconcileOnAppliedPenalty`**

`app/Listeners/ReconcileOnAppliedPenalty.php`:

```php
<?php

declare(strict_types=1);

namespace App\Listeners;

use App\Events\AppliedPenaltyPersisted;
use App\Jobs\ReconcilePenaltyHeadsJob;

final readonly class ReconcileOnAppliedPenalty
{
    public function handle(AppliedPenaltyPersisted $event): void
    {
        ReconcilePenaltyHeadsJob::dispatch($event->rake);
    }
}
```

- [ ] **Step 5: Fill in `ReconcileOnRrImport`**

`app/Listeners/ReconcileOnRrImport.php`:

```php
<?php

declare(strict_types=1);

namespace App\Listeners;

use App\Events\RrPenaltySnapshotsImported;
use App\Jobs\ReconcilePenaltyHeadsJob;

final readonly class ReconcileOnRrImport
{
    public function handle(RrPenaltySnapshotsImported $event): void
    {
        ReconcilePenaltyHeadsJob::dispatch($event->rake);
    }
}
```

- [ ] **Step 6: Confirm Laravel auto-discovers the listeners**

Laravel 13 auto-discovers listeners by their `handle()` method type-hint. Verify by running:

```bash
php artisan event:list | grep -E 'AppliedPenaltyPersisted|RrPenaltySnapshotsImported'
```

Expected: both events listed with their listener mappings. If not present, register manually in `app/Providers/EventServiceProvider.php` `$listen` array.

- [ ] **Step 7: Add a feature test for the listeners**

Create `tests/Feature/Listeners/ReconcileListenersTest.php`:

```php
<?php

declare(strict_types=1);

use App\Events\AppliedPenaltyPersisted;
use App\Events\RrPenaltySnapshotsImported;
use App\Jobs\ReconcilePenaltyHeadsJob;
use App\Models\Rake;
use Illuminate\Support\Facades\Queue;

it('queues a ReconcilePenaltyHeadsJob when AppliedPenaltyPersisted fires', function (): void {
    Queue::fake();
    $rake = Rake::factory()->create();

    AppliedPenaltyPersisted::dispatch($rake, 'demurrage');

    Queue::assertPushed(ReconcilePenaltyHeadsJob::class, fn ($job): bool => $job->rake->is($rake));
});

it('queues a ReconcilePenaltyHeadsJob when RrPenaltySnapshotsImported fires', function (): void {
    Queue::fake();
    $rake = Rake::factory()->create();

    RrPenaltySnapshotsImported::dispatch($rake);

    Queue::assertPushed(ReconcilePenaltyHeadsJob::class, fn ($job): bool => $job->rake->is($rake));
});
```

- [ ] **Step 8: Run the listener tests**

```bash
php artisan test --compact --filter=ReconcileListenersTest
```

Expected: 2 passing tests.

- [ ] **Step 9: Run pint**

```bash
vendor/bin/pint --dirty --format agent
```

- [ ] **Step 10: Commit**

```bash
git add app/Jobs/ReconcilePenaltyHeadsJob.php app/Listeners/ReconcileOnAppliedPenalty.php app/Listeners/ReconcileOnRrImport.php tests/Feature/Listeners/ReconcileListenersTest.php
git commit -m "feat(penalties): wire reconciliation listeners + Horizon job"
```

---

## Task 7: Emit `AppliedPenaltyPersisted` from existing penalty Actions

The two existing Actions that write to `applied_penalties` must emit the new event after persistence so reconciliation runs automatically.

**Files:**
- Modify: `app/Actions/ApplyDemurragePenaltyAction.php`
- Modify: `app/Actions/ApplyWeighmentPenaltiesAction.php`

- [ ] **Step 1: Add event emission to `ApplyDemurragePenaltyAction`**

In `app/Actions/ApplyDemurragePenaltyAction.php`, locate the existing `DB::afterCommit(...)` block in `handle()` (or the equivalent point right after the `AppliedPenalty` row is persisted) and append the event dispatch. Add at the top of the file:

```php
use App\Events\AppliedPenaltyPersisted;
```

Then inside the existing `DB::afterCommit(function () use ($rake, ...) ...)` callback that already runs after persistence, add:

```php
AppliedPenaltyPersisted::dispatch($rake, 'demurrage');
```

If `DB::afterCommit` is not used in that file, wrap the dispatch in `DB::afterCommit(fn () => AppliedPenaltyPersisted::dispatch($rake, 'demurrage'))` so the event fires only after the transaction commits.

- [ ] **Step 2: Add event emission to `ApplyWeighmentPenaltiesAction`**

In `app/Actions/ApplyWeighmentPenaltiesAction.php`, locate the existing `DB::afterCommit(function () use ($rake, $totalAmount, $breakdown): void { ... })` block (already present per the indexed code from earlier exploration). Add at the top of the file:

```php
use App\Events\AppliedPenaltyPersisted;
```

Inside that existing `DB::afterCommit` callback, append:

```php
AppliedPenaltyPersisted::dispatch($rake, 'weighment');
```

- [ ] **Step 3: Add a feature test asserting both Actions emit the event**

Create `tests/Feature/Actions/AppliedPenaltyEventEmissionTest.php`:

```php
<?php

declare(strict_types=1);

use App\Actions\ApplyDemurragePenaltyAction;
use App\Events\AppliedPenaltyPersisted;
use App\Models\Rake;
use Illuminate\Support\Facades\Event;

it('emits AppliedPenaltyPersisted with source=demurrage after demurrage applies', function (): void {
    Event::fake([AppliedPenaltyPersisted::class]);

    $rake = Rake::factory()->create([
        'placement_time' => now()->subHours(8),
        'loading_end_time' => now(),
        'wagon_count' => 59,
    ]);

    resolve(ApplyDemurragePenaltyAction::class)->handle($rake);

    Event::assertDispatched(AppliedPenaltyPersisted::class, function ($e) use ($rake): bool {
        return $e->rake->is($rake) && $e->source === 'demurrage';
    });
});
```

- [ ] **Step 4: Run the test**

```bash
php artisan test --compact --filter=AppliedPenaltyEventEmissionTest
```

Expected: passes (assumes the penalty-fix spec's `ApplyDemurragePenaltyAction` is already merged so demurrage actually applies for an 8h placement→load window).

If the demurrage action does not apply because the `PenaltyType['DEM']` row is missing from the test DB, run `php artisan db:seed --class=PenaltyTypeSeeder` before the test, or add it to the `RefreshDatabase`/`SeedDatabaseAfterRefresh` setup hook.

- [ ] **Step 5: Run pint**

```bash
vendor/bin/pint --dirty --format agent
```

- [ ] **Step 6: Commit**

```bash
git add app/Actions/ApplyDemurragePenaltyAction.php app/Actions/ApplyWeighmentPenaltiesAction.php tests/Feature/Actions/AppliedPenaltyEventEmissionTest.php
git commit -m "feat(penalties): emit AppliedPenaltyPersisted from existing penalty Actions"
```

---

## Task 8: Emit `RrPenaltySnapshotsImported` from RR import path

**Files:**
- Modify: `app/Services/Railway/RrImportService.php`
- Modify: `app/Imports/HistoricalRakeImport.php` (if it writes `rr_penalty_snapshots` directly)

- [ ] **Step 1: Read the existing import service**

```bash
grep -n 'rr_penalty_snapshots\|RrPenaltySnapshot' app/Services/Railway/RrImportService.php
```

Identify the method that writes/upserts `rr_penalty_snapshots` rows for a rake. This is typically a method like `importPenalties(Rake $rake, array $rows)` or similar.

- [ ] **Step 2: Add event emission after the snapshot batch**

At the top of `app/Services/Railway/RrImportService.php`, add:

```php
use App\Events\RrPenaltySnapshotsImported;
use Illuminate\Support\Facades\DB;
```

After the snapshot upsert is complete (typically the last write inside a `DB::transaction(...)` block), wrap the rake-resolved scope so the event fires post-commit. Inside the transaction, after the upsert:

```php
DB::afterCommit(fn () => RrPenaltySnapshotsImported::dispatch($rake));
```

If the import method takes a collection of rakes (one import = many rakes), dispatch one event per rake at the end of the loop, all wrapped in `DB::afterCommit`.

- [ ] **Step 3: Apply the same change to `HistoricalRakeImport`**

If `app/Imports/HistoricalRakeImport.php` writes `rr_penalty_snapshots` directly (per the earlier grep), add the same event dispatch after the per-rake snapshot upsert, again inside `DB::afterCommit`.

- [ ] **Step 4: Add a feature test**

Create `tests/Feature/Services/RrImportServiceEventTest.php`:

```php
<?php

declare(strict_types=1);

use App\Events\RrPenaltySnapshotsImported;
use App\Models\Rake;
use App\Models\RrDocument;
use App\Services\Railway\RrImportService;
use Illuminate\Support\Facades\Event;

it('emits RrPenaltySnapshotsImported once per rake after RR import', function (): void {
    Event::fake([RrPenaltySnapshotsImported::class]);

    $rake = Rake::factory()->create();
    $rrDoc = RrDocument::factory()->for($rake)->create();

    // Use the smallest possible payload that the import service accepts —
    // refer to RrImportService's existing tests if present, otherwise mirror
    // the shape produced by the parser. This step is data-shape dependent;
    // adapt to the service's existing public interface.

    resolve(RrImportService::class)->importPenaltiesForRake($rake, [
        ['penalty_code' => 'DEM', 'amount' => 1000.00],
    ]);

    Event::assertDispatched(RrPenaltySnapshotsImported::class, fn ($e): bool => $e->rake->is($rake));
});
```

If `RrImportService` does not expose a method matching the call above, find the method that accepts a single rake's worth of penalty rows and use it. The aim is to assert the event fires; the exact entry-point is whatever the existing service offers.

- [ ] **Step 5: Run the test**

```bash
php artisan test --compact --filter=RrImportServiceEventTest
```

Expected: passes.

- [ ] **Step 6: Run pint**

```bash
vendor/bin/pint --dirty --format agent
```

- [ ] **Step 7: Commit**

```bash
git add app/Services/Railway/RrImportService.php app/Imports/HistoricalRakeImport.php tests/Feature/Services/RrImportServiceEventTest.php
git commit -m "feat(penalties): emit RrPenaltySnapshotsImported after RR import writes"
```

---

## Task 9: Filament resource for `PenaltyReconciliation`

**Files:**
- Create: `app/Filament/Resources/PenaltyReconciliationResource.php` (+ generator pages)

- [ ] **Step 1: Generate the resource**

```bash
php artisan make:filament-resource PenaltyReconciliation --view --no-interaction
```

This creates the resource and `ListPenaltyReconciliations` + `ViewPenaltyReconciliation` pages.

- [ ] **Step 2: Configure the resource**

Replace the body of `app/Filament/Resources/PenaltyReconciliationResource.php` with the table definition:

```php
<?php

declare(strict_types=1);

namespace App\Filament\Resources;

use App\Filament\Resources\PenaltyReconciliationResource\Pages;
use App\Models\PenaltyReconciliation;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;

final class PenaltyReconciliationResource extends Resource
{
    protected static ?string $model = PenaltyReconciliation::class;

    protected static string | \BackedEnum | null $navigationIcon = Heroicon::Scale;

    protected static string | \UnitEnum | null $navigationGroup = 'Penalties';

    protected static ?string $navigationLabel = 'Reconciliation';

    public static function form(Schema $schema): Schema
    {
        return $schema; // read-only resource
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('rake.rake_number')->label('Rake')->searchable()->sortable(),
                TextColumn::make('penalty_code')->label('Head')->badge()->sortable(),
                TextColumn::make('predicted_amount')->money('INR')->sortable(),
                TextColumn::make('billed_amount')->money('INR')->sortable(),
                TextColumn::make('variance')->money('INR')->sortable()
                    ->color(fn (PenaltyReconciliation $r): string => $r->variance > 0 ? 'danger' : 'success'),
                TextColumn::make('variance_pct')->label('Variance %')->numeric(2)->sortable(),
                IconColumn::make('dispute_candidate')->boolean()->label('Dispute?'),
                TextColumn::make('reconciled_at')->dateTime()->sortable(),
            ])
            ->defaultSort('reconciled_at', 'desc')
            ->filters([
                SelectFilter::make('penalty_code')->options([
                    'DEM' => 'DEM',
                    'PLO' => 'PLO',
                    'POL1' => 'POL1',
                    'POLA' => 'POLA',
                    'ENHC' => 'ENHC',
                ]),
                TernaryFilter::make('dispute_candidate'),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListPenaltyReconciliations::route('/'),
            'view' => Pages\ViewPenaltyReconciliation::route('/{record}'),
        ];
    }
}
```

The view page (`Pages\ViewPenaltyReconciliation`) keeps its generated default — Filament autorenders the model fields.

- [ ] **Step 3: Smoke-check the resource loads**

```bash
php artisan test --compact --filter=ListPenaltyReconciliations 2>&1 | head -20
```

If a Filament smoke test exists in the project for similar resources, mimic it. Otherwise verify visually by running `php artisan serve` (or browsing Herd) and navigating to the admin → Penalties → Reconciliation. Do not waste time on a smoke test if the project doesn't already have one for Filament resources.

- [ ] **Step 4: Run pint**

```bash
vendor/bin/pint --dirty --format agent
```

- [ ] **Step 5: Commit**

```bash
git add app/Filament/Resources/PenaltyReconciliationResource.php app/Filament/Resources/PenaltyReconciliationResource
git commit -m "feat(filament): add PenaltyReconciliation resource (read-only, list + view)"
```

---

## Task 10: Inline reconciliation row on the rake show page

**Files:**
- Modify: `app/Http/Controllers/Rakes/RakesController.php` — pass reconciliation prop
- Modify: `resources/js/pages/rakes/show.tsx` (or whichever Inertia page renders the rake show)

- [ ] **Step 1: Locate the rake show controller method**

```bash
grep -n 'function show\|Inertia::render' app/Http/Controllers/Rakes/RakesController.php | head
```

Identify the show method (typically `show(Rake $rake)`) and the Inertia render call.

- [ ] **Step 2: Eager-load reconciliations in the controller**

In `RakesController::show()`, add `$rake->load('reconciliations')` before the `Inertia::render(...)` call. Pass the reconciliations as a prop.

First add the relationship to the `Rake` model. In `app/Models/Rake.php`:

```php
public function reconciliations(): \Illuminate\Database\Eloquent\Relations\HasMany
{
    return $this->hasMany(\App\Models\PenaltyReconciliation::class);
}
```

Then in the controller's show method, ensure the prop array passed to `Inertia::render()` includes:

```php
'reconciliations' => $rake->reconciliations->map(fn ($r) => [
    'penalty_code' => $r->penalty_code,
    'predicted_amount' => (float) $r->predicted_amount,
    'billed_amount' => (float) ($r->billed_amount ?? 0),
    'variance' => (float) $r->variance,
    'variance_pct' => $r->variance_pct,
    'dispute_candidate' => $r->dispute_candidate,
]),
```

- [ ] **Step 3: Regenerate Wayfinder typed routes**

```bash
php artisan wayfinder:generate --no-interaction
```

This refreshes `resources/js/actions/App/Http/Controllers/Rakes/RakesController.ts` with the new prop shape.

- [ ] **Step 4: Render the reconciliation row in the React page**

Locate the rake show React page — likely `resources/js/pages/rakes/show.tsx` or `resources/js/pages/rakes/[id].tsx`. Find the section that renders penalty/charge information.

Add a new section using the existing Card / Section components in the project (mirror the styling of an adjacent section — do not invent new components). Suggested code (adapt to actual component imports used in adjacent sections):

```tsx
{reconciliations.length > 0 && (
  <Card title="Penalty reconciliation">
    <table className="w-full text-sm">
      <thead>
        <tr className="text-left text-muted-foreground">
          <th>Head</th>
          <th>Predicted</th>
          <th>Billed</th>
          <th>Variance</th>
          <th>Dispute?</th>
        </tr>
      </thead>
      <tbody>
        {reconciliations.map((r) => (
          <tr key={r.penalty_code}>
            <td className="font-medium">{r.penalty_code}</td>
            <td>₹{r.predicted_amount.toLocaleString('en-IN')}</td>
            <td>₹{r.billed_amount.toLocaleString('en-IN')}</td>
            <td className={r.variance > 0 ? 'text-red-600' : 'text-green-600'}>
              {r.variance >= 0 ? '+' : ''}₹{r.variance.toLocaleString('en-IN')}
              {r.variance_pct !== null && ` (${r.variance_pct}%)`}
            </td>
            <td>{r.dispute_candidate ? '🚩' : '—'}</td>
          </tr>
        ))}
      </tbody>
    </table>
  </Card>
)}
```

Update the page's TypeScript prop type to declare `reconciliations: Reconciliation[]` (define `Reconciliation` matching the controller payload shape).

- [ ] **Step 5: Build and smoke-test**

```bash
npm run build
```

Expected: clean build, no TypeScript errors.

- [ ] **Step 6: Run pint**

```bash
vendor/bin/pint --dirty --format agent
```

- [ ] **Step 7: Commit**

```bash
git add app/Http/Controllers/Rakes/RakesController.php app/Models/Rake.php resources/js/pages/rakes/show.tsx resources/js/actions
git commit -m "feat(rakes): show penalty reconciliation row on rake detail page"
```

---

## Task 11: Migration + Model + Factory + Seeder — `commodity_utilisation_thresholds`

**Files:**
- Create: `database/migrations/2026_05_01_000002_create_commodity_utilisation_thresholds_table.php`
- Create: `app/Models/CommodityUtilisationThreshold.php`
- Create: `database/factories/CommodityUtilisationThresholdFactory.php`
- Create: `database/seeders/CommodityUtilisationThresholdSeeder.php`
- Modify: `database/seeders/DatabaseSeeder.php` — invoke the new seeder

- [ ] **Step 1: Generate migration + model + factory + seeder**

```bash
php artisan make:model CommodityUtilisationThreshold --factory --seed --migration --no-interaction
```

Rename the generated migration to `2026_05_01_000002_create_commodity_utilisation_thresholds_table.php`.

- [ ] **Step 2: Fill in the migration**

```php
<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('commodity_utilisation_thresholds', function (Blueprint $table): void {
            $table->id();
            $table->string('commodity_grade', 64);
            $table->decimal('utilisation_threshold', 4, 3);
            $table->dateTime('effective_from');
            $table->dateTime('effective_to')->nullable();
            $table->string('source', 128)->nullable(); // e.g. "IR Goods Tariff 46 §X"
            $table->timestamps();
            $table->userstamps();

            $table->index(['commodity_grade', 'effective_from']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('commodity_utilisation_thresholds');
    }
};
```

- [ ] **Step 3: Run the migration**

```bash
php artisan migrate --no-interaction
```

- [ ] **Step 4: Fill in the model**

`app/Models/CommodityUtilisationThreshold.php`:

```php
<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Wildside\Userstamps\Userstamps;

final class CommodityUtilisationThreshold extends Model
{
    use HasFactory;
    use Userstamps;

    protected $fillable = [
        'commodity_grade',
        'utilisation_threshold',
        'effective_from',
        'effective_to',
        'source',
    ];

    protected $casts = [
        'utilisation_threshold' => 'decimal:3',
        'effective_from' => 'datetime',
        'effective_to' => 'datetime',
    ];

    public static function activeFor(string $commodityGrade, ?\DateTimeInterface $at = null): ?self
    {
        $at ??= now();

        return self::query()
            ->where('commodity_grade', $commodityGrade)
            ->where('effective_from', '<=', $at)
            ->where(function ($q) use ($at) {
                $q->whereNull('effective_to')->orWhere('effective_to', '>=', $at);
            })
            ->orderByDesc('effective_from')
            ->first();
    }
}
```

- [ ] **Step 5: Fill in the factory**

`database/factories/CommodityUtilisationThresholdFactory.php`:

```php
<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\CommodityUtilisationThreshold;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<CommodityUtilisationThreshold> */
final class CommodityUtilisationThresholdFactory extends Factory
{
    protected $model = CommodityUtilisationThreshold::class;

    public function definition(): array
    {
        return [
            'commodity_grade' => $this->faker->randomElement(['G1', 'G2', 'G3', 'G4', 'G5']),
            'utilisation_threshold' => 0.95,
            'effective_from' => now()->subYear(),
            'effective_to' => null,
            'source' => 'seeded default',
        ];
    }
}
```

- [ ] **Step 6: Fill in the seeder**

`database/seeders/CommodityUtilisationThresholdSeeder.php`:

```php
<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\CommodityUtilisationThreshold;
use Illuminate\Database\Seeder;

final class CommodityUtilisationThresholdSeeder extends Seeder
{
    public function run(): void
    {
        $defaults = [
            ['commodity_grade' => 'G1', 'utilisation_threshold' => 0.95],
            ['commodity_grade' => 'G2', 'utilisation_threshold' => 0.95],
            ['commodity_grade' => 'G3', 'utilisation_threshold' => 0.95],
            ['commodity_grade' => 'G4', 'utilisation_threshold' => 0.95],
            ['commodity_grade' => 'G5', 'utilisation_threshold' => 0.95],
            ['commodity_grade' => 'UNGRADED', 'utilisation_threshold' => 0.95],
        ];

        foreach ($defaults as $row) {
            CommodityUtilisationThreshold::query()->updateOrCreate(
                ['commodity_grade' => $row['commodity_grade'], 'effective_from' => '2025-01-01 00:00:00'],
                [
                    'utilisation_threshold' => $row['utilisation_threshold'],
                    'effective_to' => null,
                    'source' => 'Stage-1 default — adjust per calibration',
                ],
            );
        }
    }
}
```

- [ ] **Step 7: Wire seeder into DatabaseSeeder**

In `database/seeders/DatabaseSeeder.php` add to the `run()` method:

```php
$this->call(CommodityUtilisationThresholdSeeder::class);
```

- [ ] **Step 8: Run the seeder**

```bash
php artisan db:seed --class=CommodityUtilisationThresholdSeeder --no-interaction
```

Expected: 6 rows seeded.

- [ ] **Step 9: Run pint**

```bash
vendor/bin/pint --dirty --format agent
```

- [ ] **Step 10: Commit**

```bash
git add database/migrations/2026_05_01_000002_create_commodity_utilisation_thresholds_table.php app/Models/CommodityUtilisationThreshold.php database/factories/CommodityUtilisationThresholdFactory.php database/seeders/CommodityUtilisationThresholdSeeder.php database/seeders/DatabaseSeeder.php
git commit -m "feat(penalties): add commodity_utilisation_thresholds with seeded defaults"
```

---

## Task 12: PLO calculator + result DTO — TDD

**Files:**
- Create: `app/Actions/PloPenaltyResult.php`
- Create: `app/Actions/CalculatePloPenaltyAction.php`
- Create: `tests/Unit/Actions/CalculatePloPenaltyActionTest.php`

- [ ] **Step 1: Create the result DTO**

`app/Actions/PloPenaltyResult.php`:

```php
<?php

declare(strict_types=1);

namespace App\Actions;

/**
 * Provisional rule per umbrella spec §5.2 — the formula may be rewritten
 * once the calibration corpus reveals the actual IR mechanism. The DTO
 * shape is the contract; the calculation inside CalculatePloPenaltyAction
 * is what changes if calibration disagrees.
 */
final readonly class PloPenaltyResult
{
    public function __construct(
        public int $rakeId,
        public float $chargeableWeightMt,
        public float $totalLoadedWeightMt,
        public float $shortfallMt,
        public float $rate,
        public float $amount,
    ) {}

    public function isApplicable(): bool
    {
        return $this->amount > 0.0;
    }
}
```

- [ ] **Step 2: Generate the test**

```bash
php artisan make:test --pest --unit CalculatePloPenaltyActionTest --no-interaction
```

- [ ] **Step 3: Fill in the test**

`tests/Unit/Actions/CalculatePloPenaltyActionTest.php`:

```php
<?php

declare(strict_types=1);

use App\Actions\CalculatePloPenaltyAction;
use App\Models\CommodityUtilisationThreshold;
use App\Models\PenaltyType;
use App\Models\Rake;
use App\Models\RakeWagonWeighment;
use App\Models\RakeWeighment;
use App\Models\Wagon;

beforeEach(function (): void {
    $this->action = resolve(CalculatePloPenaltyAction::class);
});

it('returns zero amount when total loaded equals chargeable weight', function (): void {
    $rake = Rake::factory()->create(['commodity_grade' => 'G2']);
    CommodityUtilisationThreshold::factory()->create([
        'commodity_grade' => 'G2',
        'utilisation_threshold' => 0.95,
    ]);
    $wagons = Wagon::factory()->count(58)->create(['cc_mt' => 70.0]);
    $weighment = RakeWeighment::factory()->for($rake)->create();
    foreach ($wagons as $w) {
        RakeWagonWeighment::factory()->for($weighment, 'rakeWeighment')->create([
            'wagon_id' => $w->id,
            'net_weight_mt' => 66.5, // 95% of 70
        ]);
    }

    $result = $this->action->handle($rake, $weighment);

    expect($result->shortfallMt)->toBe(0.0)
        ->and($result->amount)->toBe(0.0)
        ->and($result->isApplicable())->toBeFalse();
});

it('computes a positive amount when total loaded falls short of chargeable weight', function (): void {
    PenaltyType::query()->where('code', 'PLO')->update(['default_rate' => 100.0]);

    $rake = Rake::factory()->create(['commodity_grade' => 'G2']);
    CommodityUtilisationThreshold::factory()->create([
        'commodity_grade' => 'G2',
        'utilisation_threshold' => 0.95,
    ]);
    $wagons = Wagon::factory()->count(58)->create(['cc_mt' => 70.0]);
    $weighment = RakeWeighment::factory()->for($rake)->create();
    foreach ($wagons as $w) {
        RakeWagonWeighment::factory()->for($weighment, 'rakeWeighment')->create([
            'wagon_id' => $w->id,
            'net_weight_mt' => 60.0, // shortfall vs 66.5 chargeable per wagon
        ]);
    }

    $result = $this->action->handle($rake, $weighment);

    // 58 × (66.5 − 60) = 377 MT shortfall × ₹100 = ₹37,700
    expect(round($result->shortfallMt, 2))->toBe(377.00)
        ->and(round($result->amount, 2))->toBe(37700.00)
        ->and($result->isApplicable())->toBeTrue();
});

it('falls back to default 0.95 utilisation when no row matches the commodity grade', function (): void {
    $rake = Rake::factory()->create(['commodity_grade' => 'NEW_GRADE']);
    $wagons = Wagon::factory()->count(10)->create(['cc_mt' => 70.0]);
    $weighment = RakeWeighment::factory()->for($rake)->create();
    foreach ($wagons as $w) {
        RakeWagonWeighment::factory()->for($weighment, 'rakeWeighment')->create([
            'wagon_id' => $w->id,
            'net_weight_mt' => 66.5,
        ]);
    }

    $result = $this->action->handle($rake, $weighment);

    // 10 wagons × 70 × 0.95 = 665 MT chargeable; loaded = 665 → no shortfall
    expect($result->chargeableWeightMt)->toBe(665.0)
        ->and($result->shortfallMt)->toBe(0.0);
});
```

- [ ] **Step 4: Run the test to verify it fails**

```bash
php artisan test --compact --filter=CalculatePloPenaltyActionTest
```

Expected: failure with class-not-found.

- [ ] **Step 5: Generate and implement the Action**

```bash
php artisan make:action CalculatePloPenaltyAction --no-interaction
```

`app/Actions/CalculatePloPenaltyAction.php`:

```php
<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\CommodityUtilisationThreshold;
use App\Models\PenaltyType;
use App\Models\Rake;
use App\Models\RakeWeighment;

/**
 * CalculatePloPenaltyAction
 *
 * Provisional Penal Loading Overcharge calculator per the umbrella spec §5.2.
 * The formula is subject to rewrite once the calibration corpus confirms the
 * actual IR mechanism. The Action's input/output contract stays stable.
 *
 * Compute-only: no DB writes. Use ApplyPloPenaltyAction to persist.
 */
final readonly class CalculatePloPenaltyAction
{
    public function handle(Rake $rake, RakeWeighment $weighment): PloPenaltyResult
    {
        $threshold = CommodityUtilisationThreshold::activeFor((string) ($rake->commodity_grade ?? 'UNGRADED'));
        $utilisation = $threshold !== null ? (float) $threshold->utilisation_threshold : 0.95;

        $weighment->load('wagonWeighments.wagon');

        $chargeable = 0.0;
        $loaded = 0.0;
        foreach ($weighment->wagonWeighments as $row) {
            $chargeable += (float) ($row->wagon->cc_mt ?? 0.0) * $utilisation;
            $loaded += (float) ($row->net_weight_mt ?? 0.0);
        }

        $shortfall = max(0.0, $chargeable - $loaded);
        $rate = (float) PenaltyType::query()->where('code', 'PLO')->value('default_rate') ?? 0.0;
        $amount = round($shortfall * $rate, 2);

        return new PloPenaltyResult(
            rakeId: $rake->id,
            chargeableWeightMt: round($chargeable, 2),
            totalLoadedWeightMt: round($loaded, 2),
            shortfallMt: round($shortfall, 2),
            rate: $rate,
            amount: $amount,
        );
    }
}
```

If `Rake` does not have a `commodity_grade` column today, add it via a separate migration step before this task. Check via `php artisan tinker --execute 'echo Schema::hasColumn("rakes", "commodity_grade") ? "yes" : "no";'`. If "no", create migration `add_commodity_grade_to_rakes_table.php`:

```php
Schema::table('rakes', function (Blueprint $table): void {
    $table->string('commodity_grade', 32)->nullable()->after('rake_number');
});
```

Run the migration before completing this task.

- [ ] **Step 6: Run the test to verify it passes**

```bash
php artisan test --compact --filter=CalculatePloPenaltyActionTest
```

Expected: 3 passing tests.

- [ ] **Step 7: Run pint**

```bash
vendor/bin/pint --dirty --format agent
```

- [ ] **Step 8: Commit**

```bash
git add app/Actions/CalculatePloPenaltyAction.php app/Actions/PloPenaltyResult.php tests/Unit/Actions/CalculatePloPenaltyActionTest.php
# include the commodity_grade migration if added in step 5
git commit -m "feat(penalties): add provisional CalculatePloPenaltyAction with TDD coverage"
```

---

## Task 13: PLO persistence Action + hook into weighment finalisation — TDD

**Files:**
- Create: `app/Actions/ApplyPloPenaltyAction.php`
- Create: `tests/Feature/Actions/ApplyPloPenaltyActionTest.php`
- Modify: `app/Services/RakeWeighmentPdfImporter.php`

- [ ] **Step 1: Generate the test file**

```bash
php artisan make:test --pest ApplyPloPenaltyActionTest --no-interaction
```

- [ ] **Step 2: Fill in the test**

`tests/Feature/Actions/ApplyPloPenaltyActionTest.php`:

```php
<?php

declare(strict_types=1);

use App\Actions\ApplyPloPenaltyAction;
use App\Events\AppliedPenaltyPersisted;
use App\Models\AppliedPenalty;
use App\Models\CommodityUtilisationThreshold;
use App\Models\PenaltyType;
use App\Models\Rake;
use App\Models\RakeCharge;
use App\Models\RakeWagonWeighment;
use App\Models\RakeWeighment;
use App\Models\Wagon;
use Illuminate\Support\Facades\Event;

beforeEach(function (): void {
    PenaltyType::query()->where('code', 'PLO')->update(['default_rate' => 100.0]);
    CommodityUtilisationThreshold::factory()->create([
        'commodity_grade' => 'G2',
        'utilisation_threshold' => 0.95,
    ]);
    $this->action = resolve(ApplyPloPenaltyAction::class);
});

it('persists an AppliedPenalty row when a shortfall exists', function (): void {
    Event::fake([AppliedPenaltyPersisted::class]);

    $rake = Rake::factory()->create(['commodity_grade' => 'G2']);
    $wagons = Wagon::factory()->count(58)->create(['cc_mt' => 70.0]);
    $weighment = RakeWeighment::factory()->for($rake)->create();
    foreach ($wagons as $w) {
        RakeWagonWeighment::factory()->for($weighment, 'rakeWeighment')->create([
            'wagon_id' => $w->id,
            'net_weight_mt' => 60.0,
        ]);
    }

    $this->action->handle($rake, $weighment);

    $row = AppliedPenalty::query()
        ->where('rake_id', $rake->id)
        ->whereHas('penaltyType', fn ($q) => $q->where('code', 'PLO'))
        ->sole();

    expect((float) $row->amount)->toBe(37700.00)
        ->and($row->meta['source'])->toBe('plo')
        ->and($row->meta['shortfall_mt'])->toBe(377.00);

    Event::assertDispatched(AppliedPenaltyPersisted::class, fn ($e) => $e->source === 'plo');
});

it('does not persist a row when there is no shortfall', function (): void {
    $rake = Rake::factory()->create(['commodity_grade' => 'G2']);
    $wagons = Wagon::factory()->count(10)->create(['cc_mt' => 70.0]);
    $weighment = RakeWeighment::factory()->for($rake)->create();
    foreach ($wagons as $w) {
        RakeWagonWeighment::factory()->for($weighment, 'rakeWeighment')->create([
            'wagon_id' => $w->id,
            'net_weight_mt' => 66.5, // exactly chargeable
        ]);
    }

    $this->action->handle($rake, $weighment);

    expect(AppliedPenalty::query()->whereHas('penaltyType', fn ($q) => $q->where('code', 'PLO'))->count())->toBe(0);
});

it('updates an existing PLO row instead of duplicating on re-run (idempotent)', function (): void {
    $rake = Rake::factory()->create(['commodity_grade' => 'G2']);
    $wagons = Wagon::factory()->count(58)->create(['cc_mt' => 70.0]);
    $weighment = RakeWeighment::factory()->for($rake)->create();
    foreach ($wagons as $w) {
        RakeWagonWeighment::factory()->for($weighment, 'rakeWeighment')->create([
            'wagon_id' => $w->id,
            'net_weight_mt' => 60.0,
        ]);
    }

    $this->action->handle($rake, $weighment);
    $this->action->handle($rake, $weighment);

    expect(AppliedPenalty::query()
        ->whereHas('penaltyType', fn ($q) => $q->where('code', 'PLO'))
        ->where('rake_id', $rake->id)
        ->count()
    )->toBe(1);
});

it('recalculates the parent RakeCharge total when applied', function (): void {
    $rake = Rake::factory()->create(['commodity_grade' => 'G2']);
    $wagons = Wagon::factory()->count(58)->create(['cc_mt' => 70.0]);
    $weighment = RakeWeighment::factory()->for($rake)->create();
    foreach ($wagons as $w) {
        RakeWagonWeighment::factory()->for($weighment, 'rakeWeighment')->create([
            'wagon_id' => $w->id,
            'net_weight_mt' => 60.0,
        ]);
    }

    $this->action->handle($rake, $weighment);

    $charge = RakeCharge::query()
        ->where('rake_id', $rake->id)
        ->where('charge_type', 'PENALTY')
        ->where('is_actual_charges', false)
        ->sole();

    expect((float) $charge->amount)->toBeGreaterThanOrEqual(37700.00);
});
```

- [ ] **Step 3: Run the test to verify it fails**

```bash
php artisan test --compact --filter=ApplyPloPenaltyActionTest
```

Expected: class-not-found failures.

- [ ] **Step 4: Generate and implement the Action**

```bash
php artisan make:action ApplyPloPenaltyAction --no-interaction
```

`app/Actions/ApplyPloPenaltyAction.php`:

```php
<?php

declare(strict_types=1);

namespace App\Actions;

use App\Events\AppliedPenaltyPersisted;
use App\Models\AppliedPenalty;
use App\Models\PenaltyType;
use App\Models\Rake;
use App\Models\RakeCharge;
use App\Models\RakeWeighment;
use Illuminate\Support\Facades\DB;

final readonly class ApplyPloPenaltyAction
{
    public function __construct(private CalculatePloPenaltyAction $calculator) {}

    public function handle(Rake $rake, RakeWeighment $weighment): ?AppliedPenalty
    {
        $result = $this->calculator->handle($rake, $weighment);

        if (! $result->isApplicable()) {
            $this->removeExistingPlo($rake);
            return null;
        }

        $penaltyType = PenaltyType::query()->where('code', 'PLO')->firstOrFail();

        $applied = DB::transaction(function () use ($rake, $weighment, $result, $penaltyType): AppliedPenalty {
            $charge = RakeCharge::query()->firstOrCreate(
                [
                    'rake_id' => $rake->id,
                    'charge_type' => 'PENALTY',
                    'is_actual_charges' => false,
                ],
                [
                    'amount' => 0,
                    'data_source' => 'predicted_penalty',
                    'remarks' => 'Predicted penalty aggregate',
                ],
            );

            $row = AppliedPenalty::query()->updateOrCreate(
                [
                    'rake_id' => $rake->id,
                    'penalty_type_id' => $penaltyType->id,
                    'meta->source' => 'plo',
                ],
                [
                    'rake_charge_id' => $charge->id,
                    'wagon_id' => null,
                    'wagon_number' => null,
                    'quantity' => $result->shortfallMt,
                    'distance' => null,
                    'rate' => $result->rate,
                    'amount' => $result->amount,
                    'meta' => [
                        'source' => 'plo',
                        'rake_weighment_id' => $weighment->id,
                        'chargeable_weight_mt' => $result->chargeableWeightMt,
                        'total_loaded_weight_mt' => $result->totalLoadedWeightMt,
                        'shortfall_mt' => $result->shortfallMt,
                    ],
                ],
            );

            $this->recalculateChargeTotal($charge);

            return $row;
        });

        DB::afterCommit(fn () => AppliedPenaltyPersisted::dispatch($rake, 'plo'));

        return $applied;
    }

    private function removeExistingPlo(Rake $rake): void
    {
        AppliedPenalty::query()
            ->where('rake_id', $rake->id)
            ->where('meta->source', 'plo')
            ->delete();
    }

    private function recalculateChargeTotal(RakeCharge $charge): void
    {
        $total = AppliedPenalty::query()
            ->where('rake_charge_id', $charge->id)
            ->sum('amount');
        $charge->update(['amount' => $total]);
    }
}
```

- [ ] **Step 5: Hook into weighment finalisation**

In `app/Services/RakeWeighmentPdfImporter.php`, locate the spot where `ApplyWeighmentPenaltiesAction` is currently invoked. Inject `ApplyPloPenaltyAction` via the constructor and call it on the same finalisation path:

```php
use App\Actions\ApplyPloPenaltyAction;

// Constructor — append to existing parameters:
public function __construct(
    private ApplyWeighmentPenaltiesAction $applyWeighmentPenalties,
    private ApplyPloPenaltyAction $applyPloPenalty,
    // …other deps…
) {}

// Wherever weighment is finalised — after the existing call:
$this->applyWeighmentPenalties->handle($rake, $weighment);
$this->applyPloPenalty->handle($rake, $weighment);
```

- [ ] **Step 6: Run the test to verify it passes**

```bash
php artisan test --compact --filter=ApplyPloPenaltyActionTest
```

Expected: 4 passing tests.

- [ ] **Step 7: Run pint**

```bash
vendor/bin/pint --dirty --format agent
```

- [ ] **Step 8: Commit**

```bash
git add app/Actions/ApplyPloPenaltyAction.php tests/Feature/Actions/ApplyPloPenaltyActionTest.php app/Services/RakeWeighmentPdfImporter.php
git commit -m "feat(penalties): add ApplyPloPenaltyAction and hook into weighment finalisation"
```

---

## Task 14: Filament resource for `CommodityUtilisationThreshold`

**Files:**
- Create: `app/Filament/Resources/CommodityUtilisationThresholdResource.php` (+ pages)

- [ ] **Step 1: Generate the resource**

```bash
php artisan make:filament-resource CommodityUtilisationThreshold --no-interaction
```

- [ ] **Step 2: Configure the resource**

Replace the body of `app/Filament/Resources/CommodityUtilisationThresholdResource.php`:

```php
<?php

declare(strict_types=1);

namespace App\Filament\Resources;

use App\Filament\Resources\CommodityUtilisationThresholdResource\Pages;
use App\Models\CommodityUtilisationThreshold;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

final class CommodityUtilisationThresholdResource extends Resource
{
    protected static ?string $model = CommodityUtilisationThreshold::class;

    protected static string | \BackedEnum | null $navigationIcon = Heroicon::AdjustmentsHorizontal;

    protected static string | \UnitEnum | null $navigationGroup = 'Penalties';

    protected static ?string $navigationLabel = 'PLO Utilisation Thresholds';

    public static function form(Schema $schema): Schema
    {
        return $schema->schema([
            TextInput::make('commodity_grade')->required()->maxLength(64),
            TextInput::make('utilisation_threshold')->required()->numeric()->step(0.001),
            DateTimePicker::make('effective_from')->required(),
            DateTimePicker::make('effective_to')->nullable(),
            TextInput::make('source')->maxLength(128),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('commodity_grade')->sortable()->searchable(),
                TextColumn::make('utilisation_threshold')->numeric(3),
                TextColumn::make('effective_from')->dateTime()->sortable(),
                TextColumn::make('effective_to')->dateTime()->placeholder('Open'),
                TextColumn::make('source')->limit(40),
            ])
            ->defaultSort('effective_from', 'desc');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListCommodityUtilisationThresholds::route('/'),
            'create' => Pages\CreateCommodityUtilisationThreshold::route('/create'),
            'edit' => Pages\EditCommodityUtilisationThreshold::route('/{record}/edit'),
        ];
    }
}
```

- [ ] **Step 3: Run pint**

```bash
vendor/bin/pint --dirty --format agent
```

- [ ] **Step 4: Commit**

```bash
git add app/Filament/Resources/CommodityUtilisationThresholdResource.php app/Filament/Resources/CommodityUtilisationThresholdResource
git commit -m "feat(filament): add CommodityUtilisationThreshold resource"
```

---

## Task 15: Pakur quick-placement — controller, request, route, page

**Files:**
- Create: `app/Http/Controllers/Sidings/QuickPlacementController.php`
- Create: `app/Http/Requests/Sidings/QuickPlacementRequest.php`
- Create: `resources/js/pages/sidings/quick-placement.tsx`
- Create: `tests/Feature/Sidings/QuickPlacementTest.php`
- Modify: `routes/web.php`

- [ ] **Step 1: Write the failing feature test**

```bash
php artisan make:test --pest Sidings/QuickPlacementTest --no-interaction
```

`tests/Feature/Sidings/QuickPlacementTest.php`:

```php
<?php

declare(strict_types=1);

use App\Models\Rake;
use App\Models\Siding;
use App\Models\User;

it('lets a siding-attached user mark a rake as placed', function (): void {
    $siding = Siding::factory()->create();
    $rake = Rake::factory()->for($siding)->create(['placement_time' => null]);
    $user = User::factory()->create();
    $user->sidings()->attach($siding);
    $user->assignRole('siding_in_charge');

    $this->actingAs($user)
        ->post(route('sidings.quick-placement.store', $siding), [
            'rake_id' => $rake->id,
            'event' => 'placed',
        ])
        ->assertRedirect();

    expect($rake->fresh()->placement_time)->not->toBeNull();
});

it('rejects a user not attached to the siding', function (): void {
    $siding = Siding::factory()->create();
    $otherSiding = Siding::factory()->create();
    $rake = Rake::factory()->for($siding)->create(['placement_time' => null]);
    $user = User::factory()->create();
    $user->sidings()->attach($otherSiding); // attached elsewhere
    $user->assignRole('siding_operator');

    $this->actingAs($user)
        ->post(route('sidings.quick-placement.store', $siding), [
            'rake_id' => $rake->id,
            'event' => 'placed',
        ])
        ->assertForbidden();

    expect($rake->fresh()->placement_time)->toBeNull();
});

it('records loading_end_time when event=released', function (): void {
    $siding = Siding::factory()->create();
    $rake = Rake::factory()->for($siding)->create([
        'placement_time' => now()->subHours(3),
        'loading_end_time' => null,
    ]);
    $user = User::factory()->create();
    $user->sidings()->attach($siding);
    $user->assignRole('siding_in_charge');

    $this->actingAs($user)
        ->post(route('sidings.quick-placement.store', $siding), [
            'rake_id' => $rake->id,
            'event' => 'released',
        ])
        ->assertRedirect();

    expect($rake->fresh()->loading_end_time)->not->toBeNull();
});
```

- [ ] **Step 2: Run to verify failure**

```bash
php artisan test --compact --filter=QuickPlacementTest
```

Expected: route-not-found errors.

- [ ] **Step 3: Generate the form request**

```bash
php artisan make:request Sidings/QuickPlacementRequest --no-interaction
```

`app/Http/Requests/Sidings/QuickPlacementRequest.php`:

```php
<?php

declare(strict_types=1);

namespace App\Http\Requests\Sidings;

use App\Models\Rake;
use App\Models\Siding;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class QuickPlacementRequest extends FormRequest
{
    public function authorize(): bool
    {
        $siding = $this->route('siding');
        if (! $siding instanceof Siding) {
            return false;
        }

        return $this->user()
            ?->sidings()
            ->whereKey($siding->id)
            ->exists() ?? false;
    }

    public function rules(): array
    {
        $sidingId = $this->route('siding')?->id;

        return [
            'rake_id' => [
                'required',
                Rule::exists('rakes', 'id')->where(fn ($q) => $q->where('siding_id', $sidingId)),
            ],
            'event' => ['required', Rule::in(['placed', 'released'])],
            'occurred_at' => ['nullable', 'date'],
            'note' => ['nullable', 'string', 'max:255'],
        ];
    }
}
```

- [ ] **Step 4: Generate the controller**

```bash
php artisan make:controller Sidings/QuickPlacementController --no-interaction
```

`app/Http/Controllers/Sidings/QuickPlacementController.php`:

```php
<?php

declare(strict_types=1);

namespace App\Http\Controllers\Sidings;

use App\Http\Controllers\Controller;
use App\Http\Requests\Sidings\QuickPlacementRequest;
use App\Models\Rake;
use App\Models\Siding;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

final class QuickPlacementController extends Controller
{
    public function show(Siding $siding): Response
    {
        $rakes = Rake::query()
            ->where('siding_id', $siding->id)
            ->whereNull('dispatch_time')
            ->orderByDesc('created_at')
            ->limit(50)
            ->get(['id', 'rake_number', 'placement_time', 'loading_end_time']);

        return Inertia::render('sidings/quick-placement', [
            'siding' => ['id' => $siding->id, 'name' => $siding->name],
            'rakes' => $rakes,
        ]);
    }

    public function store(QuickPlacementRequest $request, Siding $siding): RedirectResponse
    {
        /** @var Rake $rake */
        $rake = Rake::query()->findOrFail($request->validated('rake_id'));
        $occurredAt = $request->validated('occurred_at') ?? now();

        if ($request->validated('event') === 'placed') {
            $rake->update(['placement_time' => $occurredAt]);
        } else {
            $rake->update(['loading_end_time' => $occurredAt]);
        }

        return back()->with('success', 'Recorded.');
    }
}
```

- [ ] **Step 5: Register the routes**

In `routes/web.php`, inside the authenticated middleware group, add:

```php
use App\Http\Controllers\Sidings\QuickPlacementController;

Route::get('/sidings/{siding}/quick-placement', [QuickPlacementController::class, 'show'])
    ->name('sidings.quick-placement.show');
Route::post('/sidings/{siding}/quick-placement', [QuickPlacementController::class, 'store'])
    ->name('sidings.quick-placement.store');
```

- [ ] **Step 6: Regenerate Wayfinder typed routes**

```bash
php artisan wayfinder:generate --no-interaction
```

- [ ] **Step 7: Create the Inertia React page**

`resources/js/pages/sidings/quick-placement.tsx`:

```tsx
import { Form, Head } from '@inertiajs/react'
import { router } from '@inertiajs/react'

type Rake = {
  id: number
  rake_number: string
  placement_time: string | null
  loading_end_time: string | null
}

type Props = {
  siding: { id: number; name: string }
  rakes: Rake[]
}

export default function QuickPlacement({ siding, rakes }: Props) {
  const submit = (rakeId: number, event: 'placed' | 'released') => {
    router.post(
      route('sidings.quick-placement.store', siding.id),
      { rake_id: rakeId, event },
      { preserveScroll: true },
    )
  }

  return (
    <>
      <Head title={`Quick placement — ${siding.name}`} />
      <div className="mx-auto max-w-md p-4">
        <h1 className="mb-4 text-xl font-semibold">{siding.name}</h1>
        <p className="mb-4 text-sm text-muted-foreground">
          Tap once when a rake is placed, again when released. Server stamps the time.
        </p>
        <ul className="space-y-3">
          {rakes.map((r) => (
            <li key={r.id} className="rounded-lg border p-3">
              <div className="font-medium">{r.rake_number}</div>
              <div className="mt-1 text-xs text-muted-foreground">
                {r.placement_time ? `Placed ${new Date(r.placement_time).toLocaleString()}` : 'Not placed'}
                {r.loading_end_time ? ` · Released ${new Date(r.loading_end_time).toLocaleString()}` : ''}
              </div>
              <div className="mt-3 flex gap-2">
                <button
                  type="button"
                  className="rounded-md bg-primary px-4 py-2 text-sm text-primary-foreground disabled:opacity-50"
                  disabled={r.placement_time !== null}
                  onClick={() => submit(r.id, 'placed')}
                >
                  Placed
                </button>
                <button
                  type="button"
                  className="rounded-md border px-4 py-2 text-sm disabled:opacity-50"
                  disabled={r.placement_time === null || r.loading_end_time !== null}
                  onClick={() => submit(r.id, 'released')}
                >
                  Released
                </button>
              </div>
            </li>
          ))}
        </ul>
      </div>
    </>
  )
}
```

If the project uses a different button or card primitive in adjacent siding pages, swap the inline classes for those components — read one or two siblings under `resources/js/pages/sidings/` to match the look.

- [ ] **Step 8: Run the test**

```bash
php artisan test --compact --filter=QuickPlacementTest
```

Expected: 3 passing tests.

- [ ] **Step 9: Build the frontend**

```bash
npm run build
```

Expected: clean build.

- [ ] **Step 10: Run pint**

```bash
vendor/bin/pint --dirty --format agent
```

- [ ] **Step 11: Commit**

```bash
git add app/Http/Controllers/Sidings/QuickPlacementController.php app/Http/Requests/Sidings/QuickPlacementRequest.php resources/js/pages/sidings/quick-placement.tsx routes/web.php tests/Feature/Sidings/QuickPlacementTest.php resources/js/actions
git commit -m "feat(sidings): add Pakur-style quick-placement capture page + endpoint"
```

---

## Task 16: Pakur backfill artisan command — TDD

**Files:**
- Create: `app/Console/Commands/PakurBackfillPlacementCommand.php`
- Create: `tests/Feature/Console/PakurBackfillPlacementCommandTest.php`

- [ ] **Step 1: Write the failing feature test**

```bash
php artisan make:test --pest Console/PakurBackfillPlacementCommandTest --no-interaction
```

`tests/Feature/Console/PakurBackfillPlacementCommandTest.php`:

```php
<?php

declare(strict_types=1);

use App\Models\Rake;
use App\Models\Siding;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;

it('backfills placement times from a CSV', function (): void {
    $siding = Siding::factory()->create(['name' => 'Pakur Siding']);
    $rake = Rake::factory()->for($siding)->create(['rake_number' => 'PKR-001', 'placement_time' => null]);

    $csvPath = storage_path('app/test-pakur-backfill.csv');
    File::put($csvPath, "rake_number,placed_at,released_at,source\nPKR-001,2026-04-15 10:00:00,2026-04-15 13:00:00,logbook\n");

    $this->artisan('pakur:backfill-placement', ['--file' => $csvPath])
        ->expectsOutputToContain('Updated 1 rake')
        ->assertExitCode(0);

    $rake->refresh();
    expect($rake->placement_time?->toDateTimeString())->toBe('2026-04-15 10:00:00')
        ->and($rake->loading_end_time?->toDateTimeString())->toBe('2026-04-15 13:00:00');

    File::delete($csvPath);
});

it('skips rows where rake_number does not match any rake', function (): void {
    $csvPath = storage_path('app/test-pakur-backfill.csv');
    File::put($csvPath, "rake_number,placed_at,released_at,source\nUNKNOWN,2026-04-15 10:00:00,,\n");

    $this->artisan('pakur:backfill-placement', ['--file' => $csvPath])
        ->expectsOutputToContain('Skipped 1 row')
        ->assertExitCode(0);

    File::delete($csvPath);
});

it('does not overwrite existing placement_time without --force', function (): void {
    $siding = Siding::factory()->create();
    $rake = Rake::factory()->for($siding)->create([
        'rake_number' => 'PKR-002',
        'placement_time' => '2026-04-10 08:00:00',
    ]);

    $csvPath = storage_path('app/test-pakur-backfill.csv');
    File::put($csvPath, "rake_number,placed_at,released_at,source\nPKR-002,2026-04-15 10:00:00,,\n");

    $this->artisan('pakur:backfill-placement', ['--file' => $csvPath])->assertExitCode(0);

    expect($rake->fresh()->placement_time?->toDateTimeString())->toBe('2026-04-10 08:00:00');

    File::delete($csvPath);
});
```

- [ ] **Step 2: Run to verify failure**

```bash
php artisan test --compact --filter=PakurBackfillPlacementCommandTest
```

Expected: command-not-found.

- [ ] **Step 3: Generate the command**

```bash
php artisan make:command PakurBackfillPlacementCommand --no-interaction
```

- [ ] **Step 4: Implement the command**

`app/Console/Commands/PakurBackfillPlacementCommand.php`:

```php
<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\Rake;
use Carbon\Carbon;
use Illuminate\Console\Command;

final class PakurBackfillPlacementCommand extends Command
{
    protected $signature = 'pakur:backfill-placement
                            {--file= : Path to CSV with columns rake_number,placed_at,released_at,source}
                            {--force : Overwrite existing placement_time and loading_end_time}';

    protected $description = 'Backfill placement_time and loading_end_time on rakes from a CSV file (used to recover historical Pakur data).';

    public function handle(): int
    {
        $path = (string) $this->option('file');
        if ($path === '' || ! is_file($path)) {
            $this->error('Provide a valid --file path.');
            return self::INVALID;
        }

        $force = (bool) $this->option('force');
        $updated = 0;
        $skipped = 0;
        $handle = fopen($path, 'r');
        $headers = fgetcsv($handle);

        while (($row = fgetcsv($handle)) !== false) {
            $data = array_combine($headers, $row);
            $rake = Rake::query()->where('rake_number', $data['rake_number'])->first();

            if ($rake === null) {
                $skipped++;
                $this->line("Skipped — rake_number {$data['rake_number']} not found");
                continue;
            }

            $changes = [];
            if (! empty($data['placed_at']) && ($force || $rake->placement_time === null)) {
                $changes['placement_time'] = Carbon::parse($data['placed_at']);
            }
            if (! empty($data['released_at']) && ($force || $rake->loading_end_time === null)) {
                $changes['loading_end_time'] = Carbon::parse($data['released_at']);
            }

            if ($changes === []) {
                $skipped++;
                continue;
            }

            $rake->update($changes);
            $updated++;
        }

        fclose($handle);

        $this->info("Updated {$updated} rake(s). Skipped {$skipped} row(s).");
        return self::SUCCESS;
    }
}
```

- [ ] **Step 5: Run the test**

```bash
php artisan test --compact --filter=PakurBackfillPlacementCommandTest
```

Expected: 3 passing tests.

- [ ] **Step 6: Run pint**

```bash
vendor/bin/pint --dirty --format agent
```

- [ ] **Step 7: Commit**

```bash
git add app/Console/Commands/PakurBackfillPlacementCommand.php tests/Feature/Console/PakurBackfillPlacementCommandTest.php
git commit -m "feat(pakur): add backfill artisan command for historical placement times"
```

---

## Task 17: Calibration corpus + CI gate

**Files:**
- Create: `tests/Fixtures/RailwayBills/README.md`
- Create: `tests/Fixtures/RailwayBills/2026-04-01-dumka-dem-sample-01.json`
- Create: `tests/Feature/Calibration/RrReconciliationCalibrationTest.php`
- Modify: `composer.json` (add a `test:calibration` script)

- [ ] **Step 1: Create the fixtures README**

`tests/Fixtures/RailwayBills/README.md`:

```markdown
# Railway Bills calibration corpus

JSON fixtures derived from real RR documents (PDF redacted of PII). Each file
captures one rake's billing snapshot plus the operational facts needed to
reproduce a prediction. The calibration test asserts predicted-vs-billed
within ±10% across the corpus.

## File naming

`YYYY-MM-DD-<siding-slug>-<head>-<seq>.json`

## Schema

```json
{
  "siding_name": "Dumka Siding",
  "rake_number": "DMK-2026-04-001",
  "commodity_grade": "G2",
  "wagon_count": 58,
  "placement_time": "2026-04-01T08:00:00+05:30",
  "loading_end_time": "2026-04-01T16:00:00+05:30",
  "wagons": [
    { "wagon_number": "BOXNHL-12345", "cc_mt": 70.0, "net_weight_mt": 66.5 }
  ],
  "billed": {
    "DEM": 14150,
    "PLO": 0,
    "POL1": 0,
    "POLA": 0,
    "ENHC": 0
  },
  "source_rr_document": "redacted-2026-04-01-dmk-001.pdf",
  "notes": "Free-time 180 min; load took 8h; tier 1× rate."
}
```

## How to add a sample

1. Pull a real RR document from the `rr_documents` table (or a redacted PDF).
2. Read off the placement, loading-end, wagon counts, weights, and billed amounts per head.
3. Save as a new file matching the schema above.
4. Run the calibration test to confirm predicted is within ±10% of billed for every head present.

## Why ±10%?

Tighter band invites flakes from rounding inside the railway billing system.
Looser band lets real systematic bias hide. ±10% is the working threshold —
revisit per umbrella spec §3 once the corpus has ≥30 samples.
```

- [ ] **Step 2: Add at least one real fixture**

This is the "needs human input" step. Open at least one RR document in the system (`storage/app/...`), read off the values, and save as `tests/Fixtures/RailwayBills/2026-04-01-dumka-dem-sample-01.json`. Use the schema above. Repeat for at least 9 more samples covering both DEM and PLO bills before declaring Stage 1 ready to merge.

If real fixtures are not available at plan-execution time, create one synthetic placeholder with realistic numbers and mark it `"synthetic": true` in the JSON. The calibration test should fail loudly if the corpus contains only synthetic samples on the day of merge.

- [ ] **Step 3: Generate and write the calibration test**

```bash
php artisan make:test --pest Calibration/RrReconciliationCalibrationTest --no-interaction
```

`tests/Feature/Calibration/RrReconciliationCalibrationTest.php`:

```php
<?php

declare(strict_types=1);

use App\Actions\ReconcilePenaltyHeadsAction;
use App\Models\AppliedPenalty;
use App\Models\PenaltyType;
use App\Models\Rake;
use App\Models\RakeWagonWeighment;
use App\Models\RakeWeighment;
use App\Models\RrPenaltySnapshot;
use App\Models\Siding;
use App\Models\Wagon;
use Illuminate\Support\Facades\File;

it('predicted matches billed within ±10% across the calibration corpus', function (): void {
    $dir = base_path('tests/Fixtures/RailwayBills');
    $files = collect(File::files($dir))
        ->filter(fn ($f): bool => str_ends_with($f->getFilename(), '.json'));

    expect($files)->not->toBeEmpty('Calibration corpus is empty — populate before merging Stage 1.');

    $synthetic = $files->filter(function ($f): bool {
        $j = json_decode(File::get($f->getPathname()), true);
        return ($j['synthetic'] ?? false) === true;
    });
    expect($synthetic->count())->toBeLessThan($files->count(), 'Calibration corpus contains only synthetic samples — add real RR-derived fixtures before merge.');

    $tolerance = 0.10;
    $action = resolve(ReconcilePenaltyHeadsAction::class);

    foreach ($files as $file) {
        $sample = json_decode(File::get($file->getPathname()), true);
        $rake = $this->buildRakeFromSample($sample);

        // Fire each existing penalty Action (or seed predicted rows directly to bypass them
        // when running in unit-time). Here we seed AppliedPenalty for each predicted head
        // already computed by the prior penalty-fix spec when run live.
        // For calibration we use the sample's expected predicted amounts derived from
        // running ApplyDemurragePenaltyAction + ApplyPloPenaltyAction in-process:
        \App\Actions\ApplyDemurragePenaltyAction::class;

        // Trigger reconciliation
        $action->handle($rake);

        foreach ($sample['billed'] as $code => $billedAmount) {
            if ($billedAmount === 0) {
                continue;
            }
            $row = \App\Models\PenaltyReconciliation::query()
                ->where('rake_id', $rake->id)
                ->where('penalty_code', $code)
                ->first();
            expect($row)->not->toBeNull("Missing reconciliation row for {$code} on rake {$sample['rake_number']}");

            $predicted = (float) ($row->predicted_amount ?? 0.0);
            $diffRatio = $billedAmount > 0 ? abs($predicted - $billedAmount) / $billedAmount : 0.0;
            expect($diffRatio)->toBeLessThanOrEqual(
                $tolerance,
                "{$code} prediction off by " . round($diffRatio * 100, 2) . "% on rake {$sample['rake_number']} (predicted ₹{$predicted}, billed ₹{$billedAmount})"
            );
        }
    }
});

/**
 * Build a Rake (and supporting weighment rows) from a calibration sample,
 * then drive the project's existing penalty-applying Actions so the
 * predicted_amount is what the production code path would produce.
 */
$this->buildRakeFromSample = function (array $sample): Rake {
    $siding = Siding::factory()->create(['name' => $sample['siding_name']]);
    $rake = Rake::factory()->for($siding)->create([
        'rake_number' => $sample['rake_number'],
        'commodity_grade' => $sample['commodity_grade'] ?? 'UNGRADED',
        'wagon_count' => $sample['wagon_count'],
        'placement_time' => $sample['placement_time'],
        'loading_end_time' => $sample['loading_end_time'],
    ]);

    $weighment = RakeWeighment::factory()->for($rake)->create();
    foreach ($sample['wagons'] as $w) {
        $wagon = Wagon::factory()->create([
            'wagon_number' => $w['wagon_number'],
            'cc_mt' => $w['cc_mt'],
        ]);
        RakeWagonWeighment::factory()->for($weighment, 'rakeWeighment')->create([
            'wagon_id' => $wagon->id,
            'net_weight_mt' => $w['net_weight_mt'],
        ]);
    }

    // Seed billed snapshots
    foreach ($sample['billed'] as $code => $amount) {
        if ($amount > 0) {
            RrPenaltySnapshot::factory()->for($rake)->create([
                'penalty_code' => $code,
                'amount' => $amount,
            ]);
        }
    }

    // Drive the prediction Actions so applied_penalties get populated like in prod
    resolve(\App\Actions\ApplyDemurragePenaltyAction::class)->handle($rake);
    resolve(\App\Actions\ApplyPloPenaltyAction::class)->handle($rake, $weighment);

    return $rake;
};
```

The closure-based helper keeps the test self-contained without leaking factory builders into other tests.

- [ ] **Step 4: Add the composer script**

In `composer.json`, in the `scripts` block:

```json
"test:calibration": "php artisan test --filter=RrReconciliationCalibrationTest"
```

Document in the README or contributor docs that this script must pass before merge.

- [ ] **Step 5: Run the calibration test**

```bash
composer run test:calibration
```

Expected: passes (or fails loudly because the corpus is synthetic-only). If it fails, populate at least 5 real samples before declaring Stage 1 done.

- [ ] **Step 6: Run pint**

```bash
vendor/bin/pint --dirty --format agent
```

- [ ] **Step 7: Commit**

```bash
git add tests/Fixtures/RailwayBills tests/Feature/Calibration/RrReconciliationCalibrationTest.php composer.json
git commit -m "test(penalties): add calibration corpus + reconciliation accuracy CI gate"
```

---

## Task 18: Documentation + manifest sync

**Files:**
- Create: `docs/developer/backend/actions/ReconcilePenaltyHeadsAction.md`
- Create: `docs/developer/backend/actions/CalculatePloPenaltyAction.md`
- Create: `docs/developer/backend/actions/ApplyPloPenaltyAction.md`
- Create: `docs/user-guide/sidings/quick-placement.md`
- Modify: `docs/.manifest.json`

- [ ] **Step 1: Generate Action docs from the template**

The project keeps a template at `docs/.templates/action.md`. For each new Action, copy the template into the matching path and fill in the sections.

`docs/developer/backend/actions/ReconcilePenaltyHeadsAction.md` covers:
- Purpose: predicted-vs-billed reconciliation per head, idempotent, returns `ReconciliationOutcome`.
- Trigger points: `AppliedPenaltyPersisted`, `RrPenaltySnapshotsImported` events, both via `ReconcilePenaltyHeadsJob`.
- Side effects: writes/updates `penalty_reconciliations`. Sets `dispute_candidate=true` per umbrella spec §5.1 rule.
- Related models: `PenaltyReconciliation`, `AppliedPenalty`, `RrPenaltySnapshot`, `Rake`.
- Tests: `tests/Unit/Actions/ReconcilePenaltyHeadsActionTest.php`.

`docs/developer/backend/actions/CalculatePloPenaltyAction.md`:
- Purpose: provisional PLO calculator. Pure compute, returns `PloPenaltyResult`.
- Inputs: `Rake`, `RakeWeighment`.
- Provisional formula — **call out the calibration caveat from umbrella spec §5.2**.
- Tests: `tests/Unit/Actions/CalculatePloPenaltyActionTest.php`.

`docs/developer/backend/actions/ApplyPloPenaltyAction.md`:
- Purpose: persist PLO `AppliedPenalty` rows + emit `AppliedPenaltyPersisted`.
- Trigger: invoked from `RakeWeighmentPdfImporter` after `ApplyWeighmentPenaltiesAction`.
- Idempotency note.
- Tests: `tests/Feature/Actions/ApplyPloPenaltyActionTest.php`.

- [ ] **Step 2: Generate the user-guide doc for quick-placement**

`docs/user-guide/sidings/quick-placement.md` — short, screenshot-friendly. Sections: Audience (siding-attached users), Workflow (Placed → Released), Where it shows up downstream (powers demurrage prediction). Keep under 200 lines.

- [ ] **Step 3: Update the manifest**

Open `docs/.manifest.json` and ensure each new Action and the user-guide entry is registered with `"documented": true` and the right `"path"`. If the manifest tracks Actions in an `actions` array, append new entries:

```json
{
  "name": "ReconcilePenaltyHeadsAction",
  "path": "docs/developer/backend/actions/ReconcilePenaltyHeadsAction.md",
  "documented": true,
  "lastUpdated": "2026-05-01"
}
```

Mirror for `CalculatePloPenaltyAction` and `ApplyPloPenaltyAction`.

- [ ] **Step 4: Run the manifest sync check**

```bash
php artisan docs:sync --check
```

Expected: zero warnings about undocumented items related to this stage's new code. If warnings remain for items unrelated to Stage 1 (e.g. existing pre-existing gaps), leave them — out of scope.

- [ ] **Step 5: Commit**

```bash
git add docs/developer/backend/actions docs/user-guide/sidings docs/.manifest.json
git commit -m "docs(penalties): document Stage-1 actions + Pakur quick-placement user guide"
```

---

## Stage 1 — Final integration check

Before declaring Stage 1 ready to merge, run the full suite and the calibration script:

```bash
php artisan test --compact
composer run test:calibration
vendor/bin/pint --test --format agent
```

Expected:
- Test suite green.
- Calibration test green with at least 5 real (non-synthetic) RR-derived fixtures.
- Pint reports zero formatting issues.

If anything fails, fix in place and re-commit per task convention. Do not amend earlier commits.

---

## Self-review (post-write check)

**1. Spec coverage check.** Each Stage-1 deliverable from the umbrella spec §5 is covered:

| Spec deliverable | Task |
|---|---|
| `penalty_reconciliations` table | Task 2 |
| `ReconcilePenaltyHeadsAction` + idempotency | Task 4 |
| `RrPenaltySnapshotsImported` event | Task 5 + 8 |
| `AppliedPenaltyPersisted` event | Task 5 + 7 |
| Reconciliation triggers + queue | Task 6 |
| Filament resource | Task 9 |
| Inline reconciliation row on rake show | Task 10 |
| `commodity_utilisation_thresholds` table + Filament | Task 11 + 14 |
| Provisional PLO calculator | Task 12 |
| PLO persist + RakeCharge recalc + event | Task 13 |
| Pakur quick-placement Inertia route | Task 15 |
| Pakur backfill artisan command | Task 16 |
| Calibration corpus + CI gate | Task 17 |
| Documentation + manifest update | Task 18 |
| ADR-002 (program decomposition) | Task 1 |

No spec gap remains.

**2. Placeholder scan.** No "TBD"/"TODO"/"implement later" in step bodies. Every code block is concrete. Every command is exact. Provisional PLO formula is explicitly flagged as provisional with the calibration caveat — that is intentional, not a placeholder.

**3. Type consistency.** `ReconciliationOutcome` and `PloPenaltyResult` are referenced in the same form across tasks. `AppliedPenaltyPersisted::dispatch($rake, $source)` signature is consistent across emitters and listeners. `PenaltyReconciliation` model name and column names match between migration, model, factory, action, Filament resource, and tests.

**4. Ambiguity check.** Two known live ambiguities are explicitly recorded in step 5 (PLO formula caveat) and Task 17 step 2 (calibration corpus must contain real, not synthetic, fixtures before merge). Both are flagged in the umbrella spec's Open Inputs and re-surfaced here so the executing agent doesn't silently pass the gate.

---

## Execution Handoff

Plan complete and saved to `docs/superpowers/plans/2026-05-01-penalty-savings-stage-1.md`. Two execution options:

**1. Subagent-Driven (recommended)** — fresh subagent per task, review between tasks, fast iteration. Best for this plan because tasks are independent enough to parallelise some of them (Tasks 11, 14 don't depend on Tasks 4–9).

**2. Inline Execution** — execute tasks in this session using the executing-plans skill, batch with checkpoints for review.

Which approach?
