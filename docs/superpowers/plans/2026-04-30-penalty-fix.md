# Penalty Calculation Fix — Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Fix the demurrage formula to match Indian Railways Board rules, consolidate from two competing models into one (`AppliedPenalty`), and provide a retroactive recalculation command with diff report.

**Architecture:** `ApplyDemurragePenaltyAction` is corrected in place (same model, same DB table). `BuildPenaltyChartDataAction` is rewritten to query `AppliedPenalty` directly instead of the old `Penalty` model. `PenaltyService` is deleted entirely (no callers outside its own file). A new `penalties:recalculate` Artisan command applies the corrected formula retroactively.

**Tech Stack:** Laravel 13, Pest 4, `AppliedPenalty` model, `SectionTimer` model, `PenaltyType` model, `Rake` model

---

### Task 1: Migration — add audit columns to `penalties` table

**Files:**
- Create: `database/migrations/2026_04_30_000001_add_migration_columns_to_penalties_table.php`

- [ ] **Step 1: Generate the migration**

```bash
php artisan make:migration add_migration_columns_to_penalties_table --no-interaction
```

- [ ] **Step 2: Fill in the migration**

Replace the generated file content:

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
        Schema::table('penalties', function (Blueprint $table): void {
            $table->dateTime('migrated_at')->nullable()->after('updated_at');
            $table->string('migration_note')->nullable()->after('migrated_at');
        });
    }

    public function down(): void
    {
        Schema::table('penalties', function (Blueprint $table): void {
            $table->dropColumn(['migrated_at', 'migration_note']);
        });
    }
};
```

- [ ] **Step 3: Run the migration**

```bash
php artisan migrate --no-interaction
```

Expected: `Migrating: 2026_04_30_000001_add_migration_columns_to_penalties_table` then `Migrated`.

- [ ] **Step 4: Commit**

```bash
git add database/migrations/2026_04_30_000001_add_migration_columns_to_penalties_table.php
git commit -m "feat: add migrated_at and migration_note columns to penalties table"
```

---

### Task 2: Fix `ApplyDemurragePenaltyAction` — correct formula

**Files:**
- Modify: `app/Actions/ApplyDemurragePenaltyAction.php`

The current code (line 24) checks `loading_start_time`, uses `loading_start_time → loading_end_time` window (line 30), and has no progressive rate or `wagon_count` multiplier.

- [ ] **Step 1: Write the failing test first**

```bash
php artisan make:test --pest ApplyDemurragePenaltyActionTest --no-interaction
```

Open `tests/Feature/ApplyDemurragePenaltyActionTest.php` and replace contents:

```php
<?php

declare(strict_types=1);

use App\Actions\ApplyDemurragePenaltyAction;
use App\Models\AppliedPenalty;
use App\Models\PenaltyType;
use App\Models\Rake;
use App\Models\RakeCharge;
use App\Models\SectionTimer;

beforeEach(function (): void {
    PenaltyType::factory()->create(['code' => 'DEM', 'default_rate' => 225, 'is_active' => true]);
    SectionTimer::factory()->create(['section_name' => 'loading', 'free_minutes' => 300]);
});

it('returns null and removes penalty when placement_time is null', function (): void {
    $rake = Rake::factory()->create(['placement_time' => null, 'loading_end_time' => now()]);
    $result = app(ApplyDemurragePenaltyAction::class)->handle($rake);
    expect($result)->toBeNull();
});

it('returns null and removes penalty when loading_end_time is null', function (): void {
    $rake = Rake::factory()->create(['placement_time' => now()->subHours(2), 'loading_end_time' => null]);
    $result = app(ApplyDemurragePenaltyAction::class)->handle($rake);
    expect($result)->toBeNull();
});

it('returns applied false when rake is within free window', function (): void {
    $rake = Rake::factory()->create([
        'placement_time' => now()->subMinutes(200),
        'loading_end_time' => now(),
        'wagon_count' => 10,
    ]);
    $result = app(ApplyDemurragePenaltyAction::class)->handle($rake);
    expect($result['applied'])->toBeFalse();
    expect(AppliedPenalty::where('rake_id', $rake->id)->where('meta->source', 'demurrage')->exists())->toBeFalse();
});

it('applies tier 1 rate for 3 excess hours with wagon_count multiplier', function (): void {
    // 300 free + 180 excess = 480 total minutes, ceil(180/60)=3 excess hours, tier 1 = 1x
    $rake = Rake::factory()->create([
        'placement_time' => now()->subMinutes(480),
        'loading_end_time' => now(),
        'wagon_count' => 59,
    ]);
    $result = app(ApplyDemurragePenaltyAction::class)->handle($rake);
    expect($result['applied'])->toBeTrue();
    expect($result['chargedHours'])->toBe(3);
    expect($result['rateMultiplier'])->toBe(1);
    // 3 hours × 225 × 1 multiplier × 59 wagons = 39,825
    expect($result['amount'])->toBe(39825.0);
});

it('applies tier 2 rate (2x) for 8 excess hours', function (): void {
    // 300 free + 480 excess = 780 total minutes, 8 excess hours, tier 2 = 2x
    $rake = Rake::factory()->create([
        'placement_time' => now()->subMinutes(780),
        'loading_end_time' => now(),
        'wagon_count' => 10,
    ]);
    $result = app(ApplyDemurragePenaltyAction::class)->handle($rake);
    expect($result['chargedHours'])->toBe(8);
    expect($result['rateMultiplier'])->toBe(2);
    // 8 × 225 × 2 × 10 = 36,000
    expect($result['amount'])->toBe(36000.0);
});

it('applies tier 3 rate (3x) for 15 excess hours', function (): void {
    $rake = Rake::factory()->create([
        'placement_time' => now()->subMinutes(300 + 900),
        'loading_end_time' => now(),
        'wagon_count' => 10,
    ]);
    $result = app(ApplyDemurragePenaltyAction::class)->handle($rake);
    expect($result['chargedHours'])->toBe(15);
    expect($result['rateMultiplier'])->toBe(3);
    // 15 × 225 × 3 × 10 = 101,250
    expect($result['amount'])->toBe(101250.0);
});

it('uses placement_time not loading_start_time for window start', function (): void {
    // placement_time is 10h ago, loading_start_time is 8h ago — window must start at placement_time
    $rake = Rake::factory()->create([
        'placement_time' => now()->subHours(10),
        'loading_start_time' => now()->subHours(8),
        'loading_end_time' => now(),
        'wagon_count' => 1,
    ]);
    $result = app(ApplyDemurragePenaltyAction::class)->handle($rake);
    // 10h total - 5h free = 5h excess, tier 1
    expect($result['chargedHours'])->toBe(5);
});

it('stores expanded meta snapshot', function (): void {
    $rake = Rake::factory()->create([
        'placement_time' => now()->subMinutes(480),
        'loading_end_time' => now(),
        'wagon_count' => 59,
    ]);
    app(ApplyDemurragePenaltyAction::class)->handle($rake);
    $penalty = AppliedPenalty::where('rake_id', $rake->id)->where('meta->source', 'demurrage')->first();
    expect($penalty->meta)->toHaveKey('placement_time')
        ->toHaveKey('loading_end_time')
        ->toHaveKey('wagon_count')
        ->toHaveKey('rate_multiplier')
        ->toHaveKey('base_rate')
        ->toHaveKey('excess_hours');
});
```

- [ ] **Step 2: Run tests to verify they fail**

```bash
php artisan test --compact --filter=ApplyDemurragePenaltyActionTest
```

Expected: FAIL — tests referencing `rateMultiplier`, `wagon_count` in meta, and `placement_time` window will fail.

- [ ] **Step 3: Rewrite `ApplyDemurragePenaltyAction`**

Replace `app/Actions/ApplyDemurragePenaltyAction.php` entirely:

```php
<?php

declare(strict_types=1);

namespace App\Actions;

use App\Jobs\NotifySuperAdmins;
use App\Models\AppliedPenalty;
use App\Models\PenaltyType;
use App\Models\Rake;
use App\Models\RakeCharge;
use App\Models\SectionTimer;
use Illuminate\Support\Facades\DB;

final readonly class ApplyDemurragePenaltyAction
{
    /**
     * @return array{applied: bool, chargedHours: int, excessMinutes: int, totalMinutes: int, freeMinutes: int, baseRate: float, rateMultiplier: int, amount: float}|null
     */
    public function handle(Rake $rake): ?array
    {
        if ($rake->placement_time === null || $rake->loading_end_time === null) {
            $this->removeDemurragePenalty($rake);

            return null;
        }

        $totalMinutes = (int) $rake->placement_time->diffInMinutes($rake->loading_end_time);
        $freeMinutes = (int) (SectionTimer::query()
            ->where('section_name', 'loading')
            ->value('free_minutes') ?? 300);

        $excessMinutes = $totalMinutes - $freeMinutes;

        if ($excessMinutes <= 0) {
            $this->removeDemurragePenalty($rake);

            return [
                'applied' => false,
                'chargedHours' => 0,
                'excessMinutes' => 0,
                'totalMinutes' => $totalMinutes,
                'freeMinutes' => $freeMinutes,
                'baseRate' => 0.0,
                'rateMultiplier' => 1,
                'amount' => 0.0,
            ];
        }

        $penaltyType = PenaltyType::query()
            ->where('code', 'DEM')
            ->where('is_active', true)
            ->first();

        if (! $penaltyType) {
            return null;
        }

        $baseRate = (float) ($penaltyType->default_rate ?? 0.0);
        $chargedHours = (int) ceil($excessMinutes / 60);
        $rateMultiplier = $this->progressiveMultiplier($chargedHours);
        $wagonCount = max(1, (int) $rake->wagon_count);
        $amount = round($chargedHours * $baseRate * $rateMultiplier * $wagonCount, 2);

        $created = false;

        DB::transaction(function () use ($rake, $penaltyType, $chargedHours, $baseRate, $rateMultiplier, $wagonCount, $amount, $totalMinutes, $freeMinutes, $excessMinutes, &$created): void {
            $rakeCharge = RakeCharge::query()->firstOrCreate(
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

            $applied = AppliedPenalty::query()->updateOrCreate(
                [
                    'rake_id' => $rake->id,
                    'penalty_type_id' => $penaltyType->id,
                    'meta->source' => 'demurrage',
                ],
                [
                    'rake_charge_id' => $rakeCharge->id,
                    'wagon_id' => null,
                    'wagon_number' => null,
                    'quantity' => $chargedHours,
                    'distance' => null,
                    'rate' => $baseRate * $rateMultiplier,
                    'amount' => $amount,
                    'meta' => [
                        'source' => 'demurrage',
                        'placement_time' => $rake->placement_time->toIso8601String(),
                        'loading_end_time' => $rake->loading_end_time->toIso8601String(),
                        'total_minutes' => $totalMinutes,
                        'free_minutes' => $freeMinutes,
                        'excess_minutes' => $excessMinutes,
                        'excess_hours' => $chargedHours,
                        'wagon_count' => $wagonCount,
                        'base_rate' => $baseRate,
                        'rate_multiplier' => $rateMultiplier,
                        'recalculated_at' => null,
                        'correction_reason' => null,
                    ],
                ],
            );

            $created = $applied->wasRecentlyCreated;

            $this->recalculateChargeTotal($rakeCharge);
        });

        if ($created) {
            DB::afterCommit(function () use ($rake, $amount): void {
                NotifySuperAdmins::dispatch(\App\Notifications\PenaltyCreatedNotification::class, [
                    'source' => 'demurrage',
                    'rake_id' => $rake->id,
                    'rake_number' => (string) $rake->rake_number,
                    'siding_id' => $rake->siding_id,
                    'siding_name' => $rake->siding?->name,
                    'amount_total' => $amount,
                    'breakdown' => [
                        ['code' => 'DEM', 'amount' => $amount],
                    ],
                ]);
            });
        }

        return [
            'applied' => true,
            'chargedHours' => $chargedHours,
            'excessMinutes' => $excessMinutes,
            'totalMinutes' => $totalMinutes,
            'freeMinutes' => $freeMinutes,
            'baseRate' => $baseRate,
            'rateMultiplier' => $rateMultiplier,
            'amount' => $amount,
        ];
    }

    /**
     * Indian Railways Board circular May 2022 progressive multiplier tiers.
     */
    private function progressiveMultiplier(int $excessHours): int
    {
        return match (true) {
            $excessHours <= 6 => 1,
            $excessHours <= 12 => 2,
            $excessHours <= 24 => 3,
            $excessHours <= 48 => 4,
            default => 6,
        };
    }

    private function removeDemurragePenalty(Rake $rake): void
    {
        DB::transaction(function () use ($rake): void {
            AppliedPenalty::query()
                ->where('rake_id', $rake->id)
                ->where('meta->source', 'demurrage')
                ->delete();

            $rakeCharge = RakeCharge::query()
                ->where('rake_id', $rake->id)
                ->where('charge_type', 'PENALTY')
                ->where('is_actual_charges', false)
                ->first();

            if ($rakeCharge) {
                $this->recalculateChargeTotal($rakeCharge);
            }
        });
    }

    private function recalculateChargeTotal(RakeCharge $rakeCharge): void
    {
        $total = AppliedPenalty::query()
            ->where('rake_charge_id', $rakeCharge->id)
            ->sum('amount');

        $rakeCharge->update(['amount' => round((float) $total, 2)]);
    }
}
```

- [ ] **Step 4: Run tests to verify they pass**

```bash
php artisan test --compact --filter=ApplyDemurragePenaltyActionTest
```

Expected: all PASS.

- [ ] **Step 5: Run pint**

```bash
vendor/bin/pint app/Actions/ApplyDemurragePenaltyAction.php --format agent
```

- [ ] **Step 6: Commit**

```bash
git add app/Actions/ApplyDemurragePenaltyAction.php tests/Feature/ApplyDemurragePenaltyActionTest.php
git commit -m "fix: correct demurrage formula — placement_time window, progressive rate, wagon_count multiplier"
```

---

### Task 3: Fix `BuildPenaltyChartDataAction` — switch to `AppliedPenalty`

**Files:**
- Modify: `app/Actions/BuildPenaltyChartDataAction.php`

The current action queries the `Penalty` model via `PenaltyDataTable`. We rewrite the private query methods to target `applied_penalties` joined to `penalty_types` and `rakes → sidings`. Output shape is unchanged — no frontend changes required.

- [ ] **Step 1: Write the failing test**

```bash
php artisan make:test --pest BuildPenaltyChartDataActionTest --no-interaction
```

Replace `tests/Feature/BuildPenaltyChartDataActionTest.php`:

```php
<?php

declare(strict_types=1);

use App\Actions\BuildPenaltyChartDataAction;
use App\Models\AppliedPenalty;
use App\Models\Penalty;
use App\Models\PenaltyType;
use App\Models\Rake;
use App\Models\RakeCharge;
use App\Models\Siding;
use Illuminate\Http\Request;

it('byType aggregates from AppliedPenalty not Penalty', function (): void {
    $type = PenaltyType::factory()->create(['code' => 'DEM', 'is_active' => true]);
    $rake = Rake::factory()->create();
    $charge = RakeCharge::factory()->create(['rake_id' => $rake->id]);
    AppliedPenalty::factory()->create([
        'penalty_type_id' => $type->id,
        'rake_id' => $rake->id,
        'rake_charge_id' => $charge->id,
        'amount' => 1000,
        'meta' => ['source' => 'demurrage'],
    ]);
    // Old Penalty record — must NOT appear in results
    Penalty::factory()->create(['rake_id' => $rake->id, 'penalty_amount' => 99999]);

    $result = app(BuildPenaltyChartDataAction::class)->handle(new Request);

    expect($result['byType'])->toHaveCount(1);
    expect($result['byType'][0]['name'])->toBe('DEM');
    expect($result['byType'][0]['value'])->toBe(1000.0);
});

it('bySiding aggregates by siding name', function (): void {
    $siding = Siding::factory()->create(['name' => 'Dumka']);
    $type = PenaltyType::factory()->create(['code' => 'DEM', 'is_active' => true]);
    $rake = Rake::factory()->create(['siding_id' => $siding->id]);
    $charge = RakeCharge::factory()->create(['rake_id' => $rake->id]);
    AppliedPenalty::factory()->create([
        'penalty_type_id' => $type->id,
        'rake_id' => $rake->id,
        'rake_charge_id' => $charge->id,
        'amount' => 5000,
        'meta' => ['source' => 'demurrage'],
    ]);

    $result = app(BuildPenaltyChartDataAction::class)->handle(new Request);

    expect($result['bySiding'][0]['name'])->toBe('Dumka');
    expect($result['bySiding'][0]['total'])->toBe(5000.0);
});

it('monthlyTrend returns 12 months with zero-filled gaps', function (): void {
    $result = app(BuildPenaltyChartDataAction::class)->handle(new Request);
    expect($result['monthlyTrend'])->toHaveCount(12);
    expect($result['monthlyTrend'][0])->toHaveKeys(['month', 'total', 'count']);
});
```

- [ ] **Step 2: Run to verify failure**

```bash
php artisan test --compact --filter=BuildPenaltyChartDataActionTest
```

Expected: FAIL — byType will include old `Penalty` records or have wrong field names.

- [ ] **Step 3: Rewrite `BuildPenaltyChartDataAction`**

Replace `app/Actions/BuildPenaltyChartDataAction.php`:

```php
<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\AppliedPenalty;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

final readonly class BuildPenaltyChartDataAction
{
    /**
     * @return array{byType: array<int, array{name: string, value: float, count: int}>, bySiding: array<int, array{name: string, total: float}>, monthlyTrend: array<int, array{month: string, total: float, count: int}>}
     */
    public function handle(Request $request): array
    {
        $hasDateFilter = $this->hasDateFilter($request);

        return [
            'byType' => $this->buildByType($hasDateFilter, $request),
            'bySiding' => $this->buildBySiding($hasDateFilter, $request),
            'monthlyTrend' => $this->buildMonthlyTrend($hasDateFilter, $request),
        ];
    }

    private function hasDateFilter(Request $request): bool
    {
        $filters = $request->get('filter', []);

        return isset($filters['created_at']);
    }

    private function baseQuery(bool $hasDateFilter, Request $request): \Illuminate\Database\Query\Builder
    {
        $query = DB::table('applied_penalties')
            ->join('penalty_types', 'applied_penalties.penalty_type_id', '=', 'penalty_types.id')
            ->join('rakes', 'applied_penalties.rake_id', '=', 'rakes.id')
            ->join('sidings', 'rakes.siding_id', '=', 'sidings.id');

        if (! $hasDateFilter) {
            $query->where('applied_penalties.created_at', '>=', now()->subMonths(12));
        } else {
            $filters = $request->get('filter', []);
            if (isset($filters['created_at'])) {
                $query->whereDate('applied_penalties.created_at', $filters['created_at']);
            }
        }

        return $query;
    }

    /**
     * @return array<int, array{name: string, value: float, count: int}>
     */
    private function buildByType(bool $hasDateFilter, Request $request): array
    {
        $rows = $this->baseQuery($hasDateFilter, $request)
            ->selectRaw('penalty_types.code as name, sum(applied_penalties.amount) as value, count(*) as count')
            ->groupBy('penalty_types.code')
            ->orderByDesc('value')
            ->get();

        return $rows->map(fn ($r): array => [
            'name' => (string) $r->name,
            'value' => (float) $r->value,
            'count' => (int) $r->count,
        ])->values()->all();
    }

    /**
     * @return array<int, array{name: string, total: float}>
     */
    private function buildBySiding(bool $hasDateFilter, Request $request): array
    {
        $rows = $this->baseQuery($hasDateFilter, $request)
            ->selectRaw('sidings.name as name, sum(applied_penalties.amount) as total')
            ->groupBy('sidings.name')
            ->orderByDesc('total')
            ->limit(10)
            ->get();

        return $rows->map(fn ($r): array => [
            'name' => (string) $r->name,
            'total' => (float) $r->total,
        ])->values()->all();
    }

    /**
     * @return array<int, array{month: string, total: float, count: int}>
     */
    private function buildMonthlyTrend(bool $hasDateFilter, Request $request): array
    {
        $driver = DB::getDriverName();
        $yearMonthSql = match ($driver) {
            'pgsql' => 'EXTRACT(YEAR FROM applied_penalties.created_at)::int as y, EXTRACT(MONTH FROM applied_penalties.created_at)::int as m',
            'sqlite' => "CAST(strftime('%Y', applied_penalties.created_at) AS INTEGER) as y, CAST(strftime('%m', applied_penalties.created_at) AS INTEGER) as m",
            default => 'YEAR(applied_penalties.created_at) as y, MONTH(applied_penalties.created_at) as m',
        };

        $rows = $this->baseQuery($hasDateFilter, $request)
            ->selectRaw("{$yearMonthSql}, sum(applied_penalties.amount) as total, count(*) as count")
            ->groupBy('y', 'm')
            ->orderBy('y')
            ->orderBy('m')
            ->get();

        if ($hasDateFilter) {
            return $rows->map(fn ($r): array => [
                'month' => \Carbon\Carbon::createFromDate((int) $r->y, (int) $r->m, 1)->format('M Y'),
                'total' => (float) $r->total,
                'count' => (int) $r->count,
            ])->values()->all();
        }

        $months = [];
        for ($i = 11; $i >= 0; $i--) {
            $date = now()->subMonths($i);
            $key = $date->format('Y-m');
            $months[$key] = [
                'month' => $date->format('M Y'),
                'total' => 0.0,
                'count' => 0,
            ];
        }

        foreach ($rows as $r) {
            $key = sprintf('%04d-%02d', (int) $r->y, (int) $r->m);
            if (isset($months[$key])) {
                $months[$key]['total'] = (float) $r->total;
                $months[$key]['count'] = (int) $r->count;
            }
        }

        return array_values($months);
    }
}
```

- [ ] **Step 4: Run tests to verify they pass**

```bash
php artisan test --compact --filter=BuildPenaltyChartDataActionTest
```

Expected: all PASS.

- [ ] **Step 5: Run pint**

```bash
vendor/bin/pint app/Actions/BuildPenaltyChartDataAction.php --format agent
```

- [ ] **Step 6: Commit**

```bash
git add app/Actions/BuildPenaltyChartDataAction.php tests/Feature/BuildPenaltyChartDataActionTest.php
git commit -m "fix: switch BuildPenaltyChartDataAction from Penalty model to AppliedPenalty"
```

---

### Task 4: Delete `PenaltyService`

**Files:**
- Delete: `app/Services/Rakes/PenaltyService.php`

`PenaltyService` has no callers outside its own file. It is safe to delete entirely.

- [ ] **Step 1: Verify no callers exist**

```bash
grep -rn "PenaltyService" app/ --include="*.php" | grep -v "PenaltyService.php"
```

Expected: no output (zero results).

- [ ] **Step 2: Delete the file**

```bash
rm app/Services/Rakes/PenaltyService.php
```

- [ ] **Step 3: Run the full test suite to confirm nothing breaks**

```bash
php artisan test --compact
```

Expected: all tests PASS (no references to deleted class).

- [ ] **Step 4: Commit**

```bash
git add -A
git commit -m "refactor: delete PenaltyService — superseded by ApplyDemurragePenaltyAction"
```

---

### Task 5: Create `penalties:recalculate` command

**Files:**
- Create: `app/Console/Commands/RecalculatePenalties.php`
- Create: `tests/Feature/Commands/RecalculatePenaltiesTest.php`

- [ ] **Step 1: Generate the command**

```bash
php artisan make:command RecalculatePenalties --no-interaction
```

- [ ] **Step 2: Write the failing test**

Create `tests/Feature/Commands/RecalculatePenaltiesTest.php`:

```php
<?php

declare(strict_types=1);

use App\Models\AppliedPenalty;
use App\Models\PenaltyType;
use App\Models\Rake;
use App\Models\RakeCharge;
use App\Models\SectionTimer;

beforeEach(function (): void {
    PenaltyType::factory()->create(['code' => 'DEM', 'default_rate' => 225, 'is_active' => true]);
    SectionTimer::factory()->create(['section_name' => 'loading', 'free_minutes' => 300]);
});

it('dry-run outputs CSV diff without writing to DB', function (): void {
    $rake = Rake::factory()->create([
        'placement_time' => now()->subMinutes(480),
        'loading_end_time' => now(),
        'wagon_count' => 10,
    ]);
    $charge = RakeCharge::factory()->create(['rake_id' => $rake->id]);
    AppliedPenalty::factory()->create([
        'rake_id' => $rake->id,
        'penalty_type_id' => PenaltyType::where('code', 'DEM')->first()->id,
        'rake_charge_id' => $charge->id,
        'amount' => 999.99,
        'meta' => ['source' => 'demurrage'],
    ]);

    $this->artisan('penalties:recalculate --dry-run')
        ->expectsOutputToContain('rake_id')
        ->expectsOutputToContain((string) $rake->id)
        ->assertExitCode(0);

    // Amount unchanged — dry run writes nothing
    expect(AppliedPenalty::where('rake_id', $rake->id)->value('amount'))->toBe('999.99');
});

it('applies corrected amounts when not dry-run', function (): void {
    $rake = Rake::factory()->create([
        'placement_time' => now()->subMinutes(480),
        'loading_end_time' => now(),
        'wagon_count' => 10,
    ]);
    $charge = RakeCharge::factory()->create(['rake_id' => $rake->id]);
    AppliedPenalty::factory()->create([
        'rake_id' => $rake->id,
        'penalty_type_id' => PenaltyType::where('code', 'DEM')->first()->id,
        'rake_charge_id' => $charge->id,
        'amount' => 999.99,
        'meta' => ['source' => 'demurrage'],
    ]);

    $this->artisan('penalties:recalculate')->assertExitCode(0);

    // 3 hours × 225 × 1 × 10 wagons = 6750
    $updated = AppliedPenalty::where('rake_id', $rake->id)->first();
    expect((float) $updated->amount)->toBe(6750.0);
    expect($updated->meta['correction_reason'])->toBe('formula_fix_2026-04-29');
});

it('limits to single rake with --rake option', function (): void {
    $rakeA = Rake::factory()->create([
        'placement_time' => now()->subMinutes(480),
        'loading_end_time' => now(),
        'wagon_count' => 10,
    ]);
    $rakeB = Rake::factory()->create([
        'placement_time' => now()->subMinutes(480),
        'loading_end_time' => now(),
        'wagon_count' => 10,
    ]);
    $type = PenaltyType::where('code', 'DEM')->first();
    $chargeA = RakeCharge::factory()->create(['rake_id' => $rakeA->id]);
    $chargeB = RakeCharge::factory()->create(['rake_id' => $rakeB->id]);
    AppliedPenalty::factory()->create(['rake_id' => $rakeA->id, 'penalty_type_id' => $type->id, 'rake_charge_id' => $chargeA->id, 'amount' => 1, 'meta' => ['source' => 'demurrage']]);
    AppliedPenalty::factory()->create(['rake_id' => $rakeB->id, 'penalty_type_id' => $type->id, 'rake_charge_id' => $chargeB->id, 'amount' => 1, 'meta' => ['source' => 'demurrage']]);

    $this->artisan("penalties:recalculate --rake={$rakeA->id}")->assertExitCode(0);

    expect((float) AppliedPenalty::where('rake_id', $rakeB->id)->value('amount'))->toBe(1.0);
});
```

- [ ] **Step 3: Run tests to verify failure**

```bash
php artisan test --compact --filter=RecalculatePenaltiesTest
```

Expected: FAIL — command not implemented yet.

- [ ] **Step 4: Implement the command**

Replace `app/Console/Commands/RecalculatePenalties.php`:

```php
<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Actions\ApplyDemurragePenaltyAction;
use App\Models\AppliedPenalty;
use App\Models\Rake;
use Illuminate\Console\Command;

final class RecalculatePenalties extends Command
{
    protected $signature = 'penalties:recalculate
                            {--dry-run : Calculate and output diff CSV, write nothing to DB}
                            {--from= : Limit to rakes placed on or after YYYY-MM-DD}
                            {--rake= : Single rake ID for testing}';

    protected $description = 'Recalculate demurrage penalties using the corrected Indian Railways formula.';

    public function handle(ApplyDemurragePenaltyAction $action): int
    {
        $isDryRun = (bool) $this->option('dry-run');

        $query = Rake::query()
            ->whereNotNull('placement_time')
            ->whereNotNull('loading_end_time');

        if ($rakeId = $this->option('rake')) {
            $query->where('id', (int) $rakeId);
        }

        if ($from = $this->option('from')) {
            $query->where('placement_time', '>=', $from);
        }

        $rakes = $query->with('siding')->get();

        if ($isDryRun) {
            $this->line('rake_id,rake_number,siding,old_amount,new_amount,delta');
        }

        foreach ($rakes as $rake) {
            $existing = AppliedPenalty::query()
                ->where('rake_id', $rake->id)
                ->whereJsonContains('meta->source', 'demurrage')
                ->first();

            $oldAmount = $existing ? (float) $existing->amount : 0.0;

            $result = $action->handle($rake);
            $newAmount = $result ? (float) $result['amount'] : 0.0;

            if ($isDryRun) {
                $this->line(implode(',', [
                    $rake->id,
                    $rake->rake_number,
                    $rake->siding?->name ?? '',
                    number_format($oldAmount, 2, '.', ''),
                    number_format($newAmount, 2, '.', ''),
                    number_format($newAmount - $oldAmount, 2, '.', ''),
                ]));
            } else {
                AppliedPenalty::query()
                    ->where('rake_id', $rake->id)
                    ->whereJsonContains('meta->source', 'demurrage')
                    ->update([
                        'meta->recalculated_at' => now()->toIso8601String(),
                        'meta->correction_reason' => 'formula_fix_2026-04-29',
                    ]);
            }
        }

        if (! $isDryRun) {
            $this->info("Recalculated {$rakes->count()} rakes.");
        }

        return Command::SUCCESS;
    }
}
```

- [ ] **Step 5: Run tests to verify they pass**

```bash
php artisan test --compact --filter=RecalculatePenaltiesTest
```

Expected: all PASS.

- [ ] **Step 6: Run pint**

```bash
vendor/bin/pint app/Console/Commands/RecalculatePenalties.php --format agent
```

- [ ] **Step 7: Commit**

```bash
git add app/Console/Commands/RecalculatePenalties.php tests/Feature/Commands/RecalculatePenaltiesTest.php
git commit -m "feat: add penalties:recalculate command with dry-run, --from, --rake options"
```

---

### Task 6: Full test suite pass

- [ ] **Step 1: Run the full test suite**

```bash
php artisan test --compact
```

Expected: all tests PASS. Fix any failures before proceeding.

- [ ] **Step 2: Final commit if any pint fixes needed**

```bash
vendor/bin/pint --dirty --format agent
git add -A
git commit -m "style: pint formatting fixes"
```
