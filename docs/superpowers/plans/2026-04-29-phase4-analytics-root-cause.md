# Phase 4: Penalty Analytics Augmentation — Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Augment the existing /penalties/analytics page with operator, shift, and wagon-type breakdowns plus a loader performance leaderboard — the data views managers need to attribute and prevent penalty costs.

**Architecture:** Additive only — 4 new private builder methods on PenaltyController, 4 new Inertia props, 4 new React sections in existing analytics.tsx, and a nav sidebar check.

**Tech Stack:** Laravel 13, Pest, Inertia v3, React 19, Tailwind v4, Recharts (via @/components/charts wrappers)

---

## Prerequisites / Orientation

Before starting, read these files to orient yourself:

- `app/Http/Controllers/RailwayReceipts/PenaltyController.php` — understand the `analytics()` method, existing `build*` private methods, and the `sidingIdsForUser()` call pattern.
- `resources/js/pages/penalties/analytics.tsx` — understand the existing props interface, section layout pattern, and how chart components are imported and used.
- `resources/js/components/charts/bar-chart.tsx` (or `.ts`) — understand the props interface for the BarChart wrapper, especially horizontal vs vertical orientation, data key format, and color configuration.
- `resources/js/components/app-sidebar.tsx` — find nav items and the structure of grouped nav sections.

**Critical database notes:**
- Database is PostgreSQL — use `EXTRACT(HOUR FROM wl.loading_time)` syntax, NOT MySQL `HOUR()`.
- `wagon_loading` is the exact table name (NOT `wagon_loadings`).
- `penalties` uses soft deletes — always add `->whereNull('penalties.deleted_at')` in all raw DB queries.
- `DB::table()` raw queries must include `whereNull('penalties.deleted_at')` since Eloquent's global scope does not apply.
- Import `Illuminate\Support\Facades\DB` at the top of PenaltyController if it is not already imported.

---

## Task 1: Backend — Add byOperator, byShift, byWagonType data builders to PenaltyController

**File:** `app/Http/Controllers/RailwayReceipts/PenaltyController.php`

- [ ] 1.1 Open `PenaltyController.php` and confirm `DB` facade is imported. If not, add `use Illuminate\Support\Facades\DB;` to the imports section.

- [ ] 1.2 Add private method `buildByOperator(array $sidingIds): array` immediately after the last existing `build*` method:

```php
private function buildByOperator(array $sidingIds): array
{
    $rows = DB::table('wagon_loading as wl')
        ->join('rakes', 'rakes.id', '=', 'wl.rake_id')
        ->join('penalties', 'penalties.rake_id', '=', 'rakes.id')
        ->whereIn('rakes.siding_id', $sidingIds)
        ->whereNull('penalties.deleted_at')
        ->select(
            'wl.loader_operator_name as operator',
            DB::raw('COUNT(DISTINCT penalties.id) as penalty_count'),
            DB::raw('SUM(penalties.penalty_amount) as total_amount'),
            DB::raw('SUM(CASE WHEN penalties.is_preventable THEN 1 ELSE 0 END) as preventable_count'),
        )
        ->groupBy('wl.loader_operator_name')
        ->orderByDesc('total_amount')
        ->limit(15)
        ->get();

    return $rows->map(fn ($r) => [
        'operator'         => $r->operator ?: 'Unknown',
        'penaltyCount'     => (int) $r->penalty_count,
        'totalAmount'      => (float) $r->total_amount,
        'preventableCount' => (int) $r->preventable_count,
        'preventablePct'   => $r->penalty_count > 0
            ? round(($r->preventable_count / $r->penalty_count) * 100, 1)
            : 0,
    ])->values()->all();
}
```

- [ ] 1.3 Add private method `buildByShift(array $sidingIds): array`:

```php
private function buildByShift(array $sidingIds): array
{
    $rows = DB::table('wagon_loading as wl')
        ->join('rakes', 'rakes.id', '=', 'wl.rake_id')
        ->join('penalties', 'penalties.rake_id', '=', 'rakes.id')
        ->whereIn('rakes.siding_id', $sidingIds)
        ->whereNull('penalties.deleted_at')
        ->whereNotNull('wl.loading_time')
        ->select(
            DB::raw("CASE
                WHEN EXTRACT(HOUR FROM wl.loading_time) >= 6 AND EXTRACT(HOUR FROM wl.loading_time) < 14 THEN '1st Shift'
                WHEN EXTRACT(HOUR FROM wl.loading_time) >= 14 AND EXTRACT(HOUR FROM wl.loading_time) < 22 THEN '2nd Shift'
                ELSE '3rd Shift'
            END as shift"),
            DB::raw('COUNT(DISTINCT penalties.id) as penalty_count'),
            DB::raw('SUM(penalties.penalty_amount) as total_amount'),
            DB::raw('SUM(CASE WHEN penalties.is_preventable THEN 1 ELSE 0 END) as preventable_count'),
        )
        ->groupBy('shift')
        ->orderBy('shift')
        ->get();

    $shiftOrder = ['1st Shift' => 0, '2nd Shift' => 1, '3rd Shift' => 2];

    return $rows->sortBy(fn ($r) => $shiftOrder[$r->shift] ?? 99)
        ->map(fn ($r) => [
            'shift'            => $r->shift,
            'penaltyCount'     => (int) $r->penalty_count,
            'totalAmount'      => (float) $r->total_amount,
            'preventableCount' => (int) $r->preventable_count,
        ])->values()->all();
}
```

- [ ] 1.4 Add private method `buildByWagonType(array $sidingIds): array`:

```php
private function buildByWagonType(array $sidingIds): array
{
    $rows = DB::table('wagons')
        ->join('rakes', 'rakes.id', '=', 'wagons.rake_id')
        ->join('penalties', 'penalties.rake_id', '=', 'rakes.id')
        ->whereIn('rakes.siding_id', $sidingIds)
        ->whereNull('penalties.deleted_at')
        ->select(
            DB::raw("COALESCE(NULLIF(wagons.wagon_type, ''), 'Unknown') as wagon_type"),
            DB::raw('COUNT(DISTINCT penalties.id) as penalty_count'),
            DB::raw('SUM(penalties.penalty_amount) as total_amount'),
        )
        ->groupBy('wagons.wagon_type')
        ->orderByDesc('total_amount')
        ->limit(10)
        ->get();

    return $rows->map(fn ($r) => [
        'wagonType'    => $r->wagon_type,
        'penaltyCount' => (int) $r->penalty_count,
        'totalAmount'  => (float) $r->total_amount,
    ])->values()->all();
}
```

- [ ] 1.5 In the `analytics()` method body, before the `Inertia::render()` call, add the three variable assignments alongside the existing `$byResponsibleParty`, etc. pattern:

```php
$byOperator  = $this->buildByOperator($sidingIds);
$byShift     = $this->buildByShift($sidingIds);
$byWagonType = $this->buildByWagonType($sidingIds);
```

- [ ] 1.6 Add the three new props to the `Inertia::render()` call:

```php
'byOperator'  => $byOperator,
'byShift'     => $byShift,
'byWagonType' => $byWagonType,
```

- [ ] 1.7 Run Pint to fix code style:

```bash
vendor/bin/pint app/Http/Controllers/RailwayReceipts/PenaltyController.php --format agent
```

---

## Task 2: Backend — Add operator leaderboard builder

**File:** `app/Http/Controllers/RailwayReceipts/PenaltyController.php`

- [ ] 2.1 Add private method `buildOperatorLeaderboard(array $sidingIds): array` after `buildByWagonType`:

```php
private function buildOperatorLeaderboard(array $sidingIds): array
{
    $rows = DB::table('wagon_loading as wl')
        ->join('wagons', 'wagons.id', '=', 'wl.wagon_id')
        ->join('rakes', 'rakes.id', '=', 'wl.rake_id')
        ->whereIn('rakes.siding_id', $sidingIds)
        ->whereNotNull('wl.loader_operator_name')
        ->where('wl.loader_operator_name', '!=', '')
        ->select(
            'wl.loader_operator_name as operator',
            DB::raw('COUNT(*) as total_wagons'),
            DB::raw('SUM(CASE WHEN wl.loaded_quantity_mt > wagons.pcc_weight_mt AND wagons.pcc_weight_mt > 0 THEN 1 ELSE 0 END) as overloaded_wagons'),
            DB::raw('SUM(GREATEST(wl.loaded_quantity_mt - wagons.pcc_weight_mt, 0)) as total_excess_mt'),
        )
        ->groupBy('wl.loader_operator_name')
        ->having(DB::raw('COUNT(*)'), '>=', 5)
        ->orderBy(DB::raw('SUM(CASE WHEN wl.loaded_quantity_mt > wagons.pcc_weight_mt AND wagons.pcc_weight_mt > 0 THEN 1 ELSE 0 END) * 1.0 / COUNT(*)'))
        ->limit(20)
        ->get();

    return $rows->map(fn ($r) => [
        'operator'         => $r->operator,
        'totalWagons'      => (int) $r->total_wagons,
        'overloadedWagons' => (int) $r->overloaded_wagons,
        'overloadRatePct'  => $r->total_wagons > 0
            ? round(($r->overloaded_wagons / $r->total_wagons) * 100, 1)
            : 0,
        'totalExcessMt'    => round((float) $r->total_excess_mt, 2),
    ])->values()->all();
}
```

- [ ] 2.2 In the `analytics()` method body, add:

```php
$operatorLeaderboard = $this->buildOperatorLeaderboard($sidingIds);
```

- [ ] 2.3 Add to the `Inertia::render()` call:

```php
'operatorLeaderboard' => $operatorLeaderboard,
```

- [ ] 2.4 Run Pint again:

```bash
vendor/bin/pint app/Http/Controllers/RailwayReceipts/PenaltyController.php --format agent
```

- [ ] 2.5 Commit the two backend tasks together:

```bash
git add app/Http/Controllers/RailwayReceipts/PenaltyController.php
git commit -m "feat(analytics): add byOperator, byShift, byWagonType, and operator leaderboard builders to PenaltyController"
```

---

## Task 3: Frontend — Add 3 new chart sections to analytics.tsx

**File:** `resources/js/pages/penalties/analytics.tsx`

- [ ] 3.1 Read the existing `analytics.tsx` to understand:
  - The props interface name and structure.
  - Where the existing "By Siding" section ends.
  - How `BarChart` is imported and what props it takes (especially `layout="horizontal"` for horizontal bars, `dataKey`, `nameKey`).
  - The exact card/section wrapper pattern (likely a `<div className="...">` with a heading and chart).

- [ ] 3.2 Extend the props interface with the three new data props:

```typescript
byOperator: Array<{
    operator: string;
    penaltyCount: number;
    totalAmount: number;
    preventableCount: number;
    preventablePct: number;
}>;
byShift: Array<{
    shift: string;
    penaltyCount: number;
    totalAmount: number;
    preventableCount: number;
}>;
byWagonType: Array<{
    wagonType: string;
    penaltyCount: number;
    totalAmount: number;
}>;
```

- [ ] 3.3 Destructure the new props in the component function signature alongside the existing props.

- [ ] 3.4 Add **Section A — "Penalty by Operator"** immediately after the existing "By Siding" section. Use a horizontal `BarChart`. Reference the existing "By Responsible Party" section as the pattern since it also uses a horizontal bar chart for named parties.

```tsx
{/* Penalty by Operator */}
<div className="rounded-xl border border-gray-200 bg-white p-6 shadow-sm">
    <h3 className="mb-1 text-base font-semibold text-gray-900">Penalty by Operator</h3>
    <p className="mb-4 text-sm text-gray-500">
        Top 15 loader operators by total penalty exposure. Preventable % shown per operator.
    </p>
    {byOperator.length === 0 ? (
        <p className="py-8 text-center text-sm text-gray-400">No operator data available.</p>
    ) : (
        <BarChart
            data={byOperator}
            layout="horizontal"
            dataKey="totalAmount"
            nameKey="operator"
            height={Math.max(300, byOperator.length * 32)}
            formatter={(value: number) => [`₹${value.toLocaleString()}`, 'Total Penalty']}
        />
    )}
</div>
```

> **Note:** Adjust the `BarChart` props to match the exact API of `@/components/charts/bar-chart` as observed in step 3.1. The key requirement is: operator name on Y-axis, totalAmount on X-axis, horizontal layout.

- [ ] 3.5 Add **Section B — "Penalty by Shift"** after Section A. Vertical 3-bar grouped chart showing both `penaltyCount` and `totalAmount`:

```tsx
{/* Penalty by Shift */}
<div className="rounded-xl border border-gray-200 bg-white p-6 shadow-sm">
    <h3 className="mb-1 text-base font-semibold text-gray-900">Penalty by Shift</h3>
    <p className="mb-4 text-sm text-gray-500">
        Penalty count and total exposure across 1st (06:00–14:00), 2nd (14:00–22:00), and 3rd (22:00–06:00) shifts.
    </p>
    {byShift.length === 0 ? (
        <p className="py-8 text-center text-sm text-gray-400">No shift data available.</p>
    ) : (
        <BarChart
            data={byShift}
            dataKey="totalAmount"
            nameKey="shift"
            height={280}
            formatter={(value: number) => [`₹${value.toLocaleString()}`, 'Total Penalty']}
        />
    )}
</div>
```

> **Note:** If the `BarChart` wrapper supports multiple series/bars, pass both `penaltyCount` and `totalAmount` as series. If it supports only one `dataKey`, use `totalAmount` as the primary and add a small summary table below showing `penaltyCount` per shift. Adapt to the actual wrapper API.

- [ ] 3.6 Add **Section C — "Penalty by Wagon Type"** after Section B. Horizontal bar chart:

```tsx
{/* Penalty by Wagon Type */}
<div className="rounded-xl border border-gray-200 bg-white p-6 shadow-sm">
    <h3 className="mb-1 text-base font-semibold text-gray-900">Penalty by Wagon Type</h3>
    <p className="mb-4 text-sm text-gray-500">
        Top wagon types by penalty exposure (BOXN, BCN, etc.).
    </p>
    {byWagonType.length === 0 ? (
        <p className="py-8 text-center text-sm text-gray-400">No wagon type data available.</p>
    ) : (
        <BarChart
            data={byWagonType}
            layout="horizontal"
            dataKey="totalAmount"
            nameKey="wagonType"
            height={Math.max(250, byWagonType.length * 32)}
            formatter={(value: number) => [`₹${value.toLocaleString()}`, 'Total Penalty']}
        />
    )}
</div>
```

---

## Task 4: Frontend — Add Loader Performance Leaderboard section to analytics.tsx

**File:** `resources/js/pages/penalties/analytics.tsx`

- [ ] 4.1 Extend the props interface with the leaderboard prop:

```typescript
operatorLeaderboard: Array<{
    operator: string;
    totalWagons: number;
    overloadedWagons: number;
    overloadRatePct: number;
    totalExcessMt: number;
}>;
```

- [ ] 4.2 Destructure `operatorLeaderboard` in the component function signature.

- [ ] 4.3 Add a helper function (inside or above the component) to compute the color class for overload rate:

```typescript
function overloadRateColor(pct: number): string {
    if (pct < 5) return 'text-green-600 bg-green-50';
    if (pct < 15) return 'text-amber-600 bg-amber-50';
    return 'text-red-600 bg-red-50';
}

function overloadRateBarColor(pct: number): string {
    if (pct < 5) return 'bg-green-500';
    if (pct < 15) return 'bg-amber-500';
    return 'bg-red-500';
}
```

- [ ] 4.4 Add rank badge helper:

```typescript
function rankBadge(rank: number): string {
    if (rank === 1) return '🥇';
    if (rank === 2) return '🥈';
    if (rank === 3) return '🥉';
    return `#${rank}`;
}
```

- [ ] 4.5 Add the **"Loader Performance Leaderboard"** section at the very end of the analytics page, after the last existing section:

```tsx
{/* Loader Performance Leaderboard */}
<div className="rounded-xl border border-gray-200 bg-white p-6 shadow-sm">
    <div className="mb-4 flex items-start justify-between">
        <div>
            <h3 className="text-base font-semibold text-gray-900">Loader Performance Leaderboard</h3>
            <p className="mt-1 text-sm text-gray-500">
                Ranked by lowest overload rate (overloaded wagons ÷ total wagons). Lower is better.
                Minimum 5 wagons loaded to qualify.
            </p>
        </div>
        <span className="rounded-full bg-green-100 px-3 py-1 text-xs font-medium text-green-700">
            Lower is better
        </span>
    </div>

    {operatorLeaderboard.length === 0 ? (
        <p className="py-8 text-center text-sm text-gray-400">
            No operator data available for the current period.
        </p>
    ) : (
        <div className="divide-y divide-gray-100">
            {operatorLeaderboard.map((row, index) => {
                const rank = index + 1;
                const colorClass = overloadRateColor(row.overloadRatePct);
                const barColor = overloadRateBarColor(row.overloadRatePct);
                const badgeText = rankBadge(rank);
                const isEmoji = rank <= 3;

                return (
                    <div key={row.operator} className="flex items-center gap-4 py-3">
                        {/* Rank */}
                        <div className="w-10 shrink-0 text-center">
                            {isEmoji ? (
                                <span className="text-lg">{badgeText}</span>
                            ) : (
                                <span className="text-sm font-semibold text-gray-400">{badgeText}</span>
                            )}
                        </div>

                        {/* Operator name + stats */}
                        <div className="min-w-0 flex-1">
                            <div className="flex items-center justify-between">
                                <span className="truncate text-sm font-medium text-gray-900">
                                    {row.operator}
                                </span>
                                <span className={`ml-3 shrink-0 rounded-full px-2 py-0.5 text-xs font-semibold ${colorClass}`}>
                                    {row.overloadRatePct}% overload
                                </span>
                            </div>
                            {/* Progress bar */}
                            <div className="mt-1.5 h-1.5 w-full rounded-full bg-gray-100">
                                <div
                                    className={`h-1.5 rounded-full transition-all ${barColor}`}
                                    style={{ width: `${Math.min(row.overloadRatePct, 100)}%` }}
                                />
                            </div>
                            {/* Sub-stats */}
                            <div className="mt-1 flex gap-4 text-xs text-gray-400">
                                <span>{row.totalWagons} wagons loaded</span>
                                <span>{row.overloadedWagons} overloaded</span>
                                <span>{row.totalExcessMt.toFixed(1)} MT excess</span>
                            </div>
                        </div>
                    </div>
                );
            })}
        </div>
    )}

    <p className="mt-4 border-t border-gray-100 pt-3 text-xs text-gray-400">
        Rankings based on wagons loaded in current filtered period. Operators with fewer than 5 wagons
        are excluded.
    </p>
</div>
```

- [ ] 4.6 Run TypeScript type check to confirm no errors:

```bash
npx tsc --noEmit
```

Fix any type errors before proceeding.

- [ ] 4.7 Commit the frontend changes:

```bash
git add resources/js/pages/penalties/analytics.tsx
git commit -m "feat(analytics): add operator, shift, wagon-type chart sections and loader performance leaderboard"
```

---

## Task 5: Check and add sidebar nav item

**File:** `resources/js/components/app-sidebar.tsx`

- [ ] 5.1 Read `resources/js/components/app-sidebar.tsx` fully. Search for any existing reference to `penalties/analytics`, `penalties.analytics`, or `PenaltyAnalytics`.

- [ ] 5.2 Determine the nav group structure. Look for groups like "Operations", "Finance & Compliance", or "Penalties". Identify which group the existing `/penalties` list item lives in — the analytics item should go in the same group, immediately after it.

- [ ] 5.3 Check if `BarChart3` is already imported from `lucide-react`. If not, add it to the existing lucide-react import line.

- [ ] 5.4 If the `/penalties/analytics` nav item is **missing**, add it using the pattern from sibling items. The item should look like this (adjust to match the exact nav item object shape used in this file):

```typescript
{
    title: 'Penalty Analytics',
    href: route('penalties.analytics'),   // use Wayfinder import if the file uses it, otherwise use route()
    icon: BarChart3,
    permission: 'sections.penalties.view',
    dataPan: 'nav-penalty-analytics',
}
```

> **Note:** If the sidebar uses Wayfinder (`PenaltyController.analytics()`) rather than `route()`, use the Wayfinder equivalent. Check the sibling penalty list item to see which pattern is used.

- [ ] 5.5 Register the `data-pan` name. Open `app/Providers/AppServiceProvider.php` and find `configurePan()`. Add `'nav-penalty-analytics'` to the `allowedAnalytics` array.

- [ ] 5.6 Run Pint on the PHP file:

```bash
vendor/bin/pint app/Providers/AppServiceProvider.php --format agent
```

- [ ] 5.7 Run the production build to catch any TypeScript or bundler errors:

```bash
npm run build
```

If there are errors, fix them before proceeding.

- [ ] 5.8 Commit sidebar and AppServiceProvider changes:

```bash
git add resources/js/components/app-sidebar.tsx app/Providers/AppServiceProvider.php
git commit -m "feat(nav): add Penalty Analytics sidebar nav item and register pan event"
```

---

## Task 6: Verification

- [ ] 6.1 Run the full test suite to confirm nothing is broken:

```bash
php artisan test --compact
```

- [ ] 6.2 If the project has feature tests for `PenaltyController::analytics`, verify they still pass. If no tests exist for the analytics endpoint, add a smoke test:

```bash
php artisan make:test --pest PenaltyAnalyticsAugmentationTest
```

The test should:
- Act as a user with `sections.penalties.view` permission.
- Call `GET /penalties/analytics`.
- Assert the response contains the four new Inertia props: `byOperator`, `byShift`, `byWagonType`, `operatorLeaderboard`.
- Assert each prop is an array (may be empty in test environment with no data).

Example test structure:

```php
<?php

use App\Models\User;

it('returns augmented analytics props', function () {
    $user = User::factory()->create();
    // assign relevant role/permission if needed

    $response = $this->actingAs($user)
        ->get(route('penalties.analytics'));

    $response->assertInertia(fn ($page) => $page
        ->has('byOperator')
        ->has('byShift')
        ->has('byWagonType')
        ->has('operatorLeaderboard')
    );
});
```

- [ ] 6.3 Verify the page renders in browser by visiting `/penalties/analytics` and confirming:
  - The 3 new chart sections appear after "By Siding".
  - The "Loader Performance Leaderboard" appears at the bottom.
  - The sidebar shows "Penalty Analytics" in the nav (for manager/exec roles).
  - No console errors.

- [ ] 6.4 Final commit (if any remaining files):

```bash
git add -p   # review and stage any remaining files
git commit -m "test(analytics): add smoke test for augmented penalty analytics props"
```

---

## Summary of Changes

| File | Change |
|------|--------|
| `app/Http/Controllers/RailwayReceipts/PenaltyController.php` | +4 private methods: `buildByOperator`, `buildByShift`, `buildByWagonType`, `buildOperatorLeaderboard`; +4 Inertia props in `analytics()` |
| `resources/js/pages/penalties/analytics.tsx` | +4 prop types; +4 new sections (Operator chart, Shift chart, Wagon Type chart, Leaderboard) |
| `resources/js/components/app-sidebar.tsx` | Add `Penalty Analytics` nav item if missing |
| `app/Providers/AppServiceProvider.php` | Register `nav-penalty-analytics` in Pan allowedAnalytics |
| `tests/Feature/PenaltyAnalyticsAugmentationTest.php` | Smoke test for new Inertia props |
