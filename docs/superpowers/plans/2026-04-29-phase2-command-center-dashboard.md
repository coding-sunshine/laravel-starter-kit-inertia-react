# Phase 2: Operations Command Center Dashboard — Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Add a "Command Center" section at the top of the existing dashboard with 6 penalty-intelligence widgets for manager/exec roles and a single rake widget for operators. Backend adds 5 new additive Inertia props — no existing props removed or modified.

**Architecture:** 4 new private methods in `ExecutiveDashboardController` → 5 new Inertia props → 7 new React components in `resources/js/components/dashboard/` → new section injected at top of `dashboard.tsx`.

**Tech Stack:** Laravel 13, Inertia v3, React 19, Tailwind v4, shadcn/ui, Recharts (already installed)

---

## File Map

| File | Action |
|------|--------|
| `app/Http/Controllers/Dashboard/ExecutiveDashboardController.php` | Modify — add 4 private methods + 5 new props |
| `resources/js/pages/dashboard.tsx` | Modify — add new TS interfaces + Command Center section at top |
| `resources/js/components/dashboard/penalty-exposure-strip.tsx` | Create |
| `resources/js/components/dashboard/active-rake-pipeline.tsx` | Create |
| `resources/js/components/dashboard/siding-coal-stock.tsx` | Create |
| `resources/js/components/dashboard/siding-risk-score.tsx` | Create |
| `resources/js/components/dashboard/alert-feed.tsx` | Create |
| `resources/js/components/dashboard/dispatch-summary.tsx` | Create |
| `resources/js/components/dashboard/operator-rake-widget.tsx` | Create |

---

### Task 1: Add `buildPenaltySummary()` to ExecutiveDashboardController

**Files:**
- Modify: `app/Http/Controllers/Dashboard/ExecutiveDashboardController.php`

- [ ] **Step 1: Add the private method**

Add this private method to `ExecutiveDashboardController` (before the closing `}`):

```php
private function buildPenaltySummary(array $sidingIds): array
{
    try {
        $today = Carbon::today();
        $sevenDaysAgo = $today->copy()->subDays(6);

        $base = Penalty::whereHas('rake', fn ($q) => $q->whereIn('siding_id', $sidingIds));

        $todayTotal = (float) $base->clone()
            ->whereDate('penalty_date', $today)
            ->sum('penalty_amount');

        $trend = $base->clone()
            ->whereBetween('penalty_date', [$sevenDaysAgo, $today])
            ->selectRaw('DATE(penalty_date) as date, SUM(penalty_amount) as rs')
            ->groupBy('date')
            ->orderBy('date')
            ->get()
            ->map(fn ($r) => ['date' => $r->date, 'rs' => (float) $r->rs])
            ->toArray();

        $totalInPeriod = array_sum(array_column($trend, 'rs'));
        $preventableInPeriod = (float) $base->clone()
            ->whereBetween('penalty_date', [$sevenDaysAgo, $today])
            ->where('is_preventable', true)
            ->sum('penalty_amount');

        $preventablePct = $totalInPeriod > 0
            ? round(($preventableInPeriod / $totalInPeriod) * 100, 1)
            : 0.0;

        return [
            'today_rs'        => $todayTotal,
            'trend_7d'        => $trend,
            'preventable_pct' => $preventablePct,
        ];
    } catch (\Throwable) {
        return ['today_rs' => 0.0, 'trend_7d' => [], 'preventable_pct' => 0.0];
    }
}
```

- [ ] **Step 2: Add `penaltySummary` to the `__invoke` return array**

In the `__invoke` method, add this line alongside the existing props (e.g., after the `'sidingStocks'` line):

```php
'penaltySummary' => $this->buildPenaltySummary($sidingIds),
```

- [ ] **Step 3: Verify no syntax errors**

```bash
php artisan route:list --name=dashboard 2>&1 | head -5
```

Expected: route listed without PHP error.

- [ ] **Step 4: Commit**

```bash
git add app/Http/Controllers/Dashboard/ExecutiveDashboardController.php
git commit -m "feat(dashboard): add penaltySummary Inertia prop"
```

---

### Task 2: Add `buildActiveRakePipeline()` to ExecutiveDashboardController

**Files:**
- Modify: `app/Http/Controllers/Dashboard/ExecutiveDashboardController.php`

- [ ] **Step 1: Add the private method**

```php
private function buildActiveRakePipeline(array $sidingIds): array
{
    try {
        $rakes = Rake::whereIn('siding_id', $sidingIds)
            ->with(['siding:id,name,code', 'wagonLoadings.wagon:id,pcc_weight_mt'])
            ->whereNotIn('state', ['dispatched', 'completed', 'cancelled'])
            ->orWhere(fn ($q) => $q->whereIn('siding_id', $sidingIds)
                ->where('state', 'dispatched')
                ->whereDate('dispatch_time', Carbon::today()))
            ->orderByDesc('loading_date')
            ->limit(50)
            ->get();

        $pipeline = ['loading' => [], 'awaiting_clearance' => [], 'dispatched' => []];

        foreach ($rakes as $rake) {
            $overloadedCount = 0;
            $penaltyRiskRs   = 0.0;

            foreach ($rake->wagonLoadings as $wl) {
                $pcc = (float) ($wl->wagon?->pcc_weight_mt ?? $wl->cc_capacity_mt ?? 0);
                $loaded = (float) $wl->loaded_quantity_mt;
                if ($pcc > 0 && $loaded > $pcc) {
                    $overloadedCount++;
                    // Indian Railways overload penalty rate: ₹6 per 100kg excess (simplified)
                    $excessMt = $loaded - $pcc;
                    $penaltyRiskRs += round($excessMt * 1000 * 0.06, 2);
                }
            }

            $card = [
                'rake_id'          => $rake->id,
                'rake_number'      => $rake->rake_number,
                'siding_name'      => $rake->siding?->name ?? '—',
                'siding_code'      => $rake->siding?->code ?? '—',
                'wagon_count'      => (int) $rake->wagon_count,
                'overloaded_count' => $overloadedCount,
                'penalty_risk_rs'  => $penaltyRiskRs,
                'state'            => $rake->state,
                'loading_date'     => $rake->loading_date?->toDateString(),
            ];

            $bucket = match (true) {
                str_contains((string) $rake->state, 'dispatch') => 'dispatched',
                str_contains((string) $rake->state, 'clearance') || str_contains((string) $rake->state, 'await') => 'awaiting_clearance',
                default => 'loading',
            };

            $pipeline[$bucket][] = $card;
        }

        return $pipeline;
    } catch (\Throwable) {
        return ['loading' => [], 'awaiting_clearance' => [], 'dispatched' => []];
    }
}
```

- [ ] **Step 2: Add `activeRakePipeline` to the `__invoke` return array**

```php
'activeRakePipeline' => $this->buildActiveRakePipeline($sidingIds),
```

- [ ] **Step 3: Verify**

```bash
php artisan route:list --name=dashboard 2>&1 | head -5
```

- [ ] **Step 4: Commit**

```bash
git add app/Http/Controllers/Dashboard/ExecutiveDashboardController.php
git commit -m "feat(dashboard): add activeRakePipeline Inertia prop with overload counts"
```

---

### Task 3: Add `riskScores`, `alerts`, and `operatorRake` props

**Files:**
- Modify: `app/Http/Controllers/Dashboard/ExecutiveDashboardController.php`

- [ ] **Step 1: Add `buildRiskScores()` private method**

```php
private function buildRiskScores(array $sidingIds): array
{
    try {
        return SidingRiskScore::whereIn('siding_id', $sidingIds)
            ->with('siding:id,name,code')
            ->get()
            ->map(fn ($r) => [
                'siding_id'    => $r->siding_id,
                'siding_name'  => $r->siding?->name ?? '—',
                'siding_code'  => $r->siding?->code ?? '—',
                'score'        => (int) $r->score,
                'trend'        => $r->trend,
                'risk_factors' => $r->risk_factors ?? [],
                'calculated_at'=> $r->calculated_at?->toDateTimeString(),
            ])
            ->keyBy('siding_id')
            ->toArray();
    } catch (\Throwable) {
        return [];
    }
}
```

- [ ] **Step 2: Add `buildAlerts()` private method**

```php
private function buildAlerts(array $sidingIds): array
{
    try {
        return Alert::forSidings($sidingIds)
            ->active()
            ->orderByDesc('created_at')
            ->limit(30)
            ->get()
            ->map(fn ($a) => [
                'id'         => $a->id,
                'type'       => $a->type,
                'title'      => $a->title,
                'body'       => $a->body,
                'severity'   => $a->severity,
                'siding_id'  => $a->siding_id,
                'rake_id'    => $a->rake_id,
                'created_at' => $a->created_at?->diffForHumans(),
            ])
            ->groupBy('severity')
            ->toArray();
    } catch (\Throwable) {
        return [];
    }
}
```

- [ ] **Step 3: Add `buildOperatorRake()` private method**

```php
private function buildOperatorRake(array $sidingIds): ?array
{
    try {
        $rake = Rake::whereIn('siding_id', $sidingIds)
            ->whereNotIn('state', ['dispatched', 'completed', 'cancelled'])
            ->with('siding:id,name,code')
            ->orderByDesc('loading_date')
            ->first();

        if (! $rake) {
            return null;
        }

        $metrics = $rake->rakeLoaderProgressMetrics();

        return [
            'rake_id'      => $rake->id,
            'rake_number'  => $rake->rake_number,
            'siding_name'  => $rake->siding?->name ?? '—',
            'state'        => $rake->state,
            'loaded'       => $metrics['loaded'] ?? 0,
            'total'        => $metrics['total'] ?? 0,
            'status'       => $metrics['status'] ?? 'none',
            'loading_date' => $rake->loading_date?->toDateString(),
        ];
    } catch (\Throwable) {
        return null;
    }
}
```

- [ ] **Step 4: Add all three props to `__invoke` return array**

```php
'riskScores'    => $this->buildRiskScores($sidingIds),
'alerts'        => $this->buildAlerts($sidingIds),
'operatorRake'  => $this->buildOperatorRake($sidingIds),
```

- [ ] **Step 5: Check the model imports at the top of the controller — ensure `SidingRiskScore` and `Alert` are imported**

Look at the existing `use` statements in the controller. If `App\Models\SidingRiskScore` and `App\Models\Alert` are not present, add them.

- [ ] **Step 6: Verify and commit**

```bash
php artisan route:list --name=dashboard 2>&1 | head -5
git add app/Http/Controllers/Dashboard/ExecutiveDashboardController.php
git commit -m "feat(dashboard): add riskScores, alerts, operatorRake Inertia props"
```

---

### Task 4: Add TypeScript interfaces + DashboardProps to `dashboard.tsx`

**Files:**
- Modify: `resources/js/pages/dashboard.tsx`

- [ ] **Step 1: Add new interfaces after the existing `LiveRakeStatusRow` interface**

Find the existing interfaces section in `dashboard.tsx` and insert these new interfaces after `LiveRakeStatusRow`:

```typescript
interface PenaltySummary {
    today_rs: number;
    trend_7d: { date: string; rs: number }[];
    preventable_pct: number;
}

interface RakePipelineCard {
    rake_id: number;
    rake_number: string;
    siding_name: string;
    siding_code: string;
    wagon_count: number;
    overloaded_count: number;
    penalty_risk_rs: number;
    state: string;
    loading_date: string | null;
}

interface ActiveRakePipeline {
    loading: RakePipelineCard[];
    awaiting_clearance: RakePipelineCard[];
    dispatched: RakePipelineCard[];
}

interface SidingRiskScoreData {
    siding_id: number;
    siding_name: string;
    siding_code: string;
    score: number;
    trend: string;
    risk_factors: string[];
    calculated_at: string | null;
}

interface AlertRecord {
    id: number;
    type: string;
    title: string;
    body: string;
    severity: string;
    siding_id: number | null;
    rake_id: number | null;
    created_at: string;
}

interface OperatorRake {
    rake_id: number;
    rake_number: string;
    siding_name: string;
    state: string;
    loaded: number;
    total: number;
    status: string;
    loading_date: string | null;
}
```

- [ ] **Step 2: Add new props to `DashboardProps` type**

Find `type DashboardProps = SharedData & {` and add inside it:

```typescript
    penaltySummary?: PenaltySummary;
    activeRakePipeline?: ActiveRakePipeline;
    riskScores?: Record<number, SidingRiskScoreData>;
    alerts?: Record<string, AlertRecord[]>;
    operatorRake?: OperatorRake | null;
```

- [ ] **Step 3: Build to verify TypeScript compiles**

```bash
npm run build 2>&1 | tail -5
```

Expected: `✓ built in X.XXs`

- [ ] **Step 4: Commit**

```bash
git add resources/js/pages/dashboard.tsx
git commit -m "feat(dashboard): add Phase 2 TypeScript interfaces to dashboard props"
```

---

### Task 5: Create `PenaltyExposureStrip` component

**Files:**
- Create: `resources/js/components/dashboard/penalty-exposure-strip.tsx`

- [ ] **Step 1: Create the component**

```tsx
import { Card, CardContent } from '@/components/ui/card';
import { Badge } from '@/components/ui/badge';
import { AreaChart, Area, ResponsiveContainer, Tooltip } from 'recharts';

interface PenaltySummary {
    today_rs: number;
    trend_7d: { date: string; rs: number }[];
    preventable_pct: number;
}

export function PenaltyExposureStrip({ data }: { data: PenaltySummary }) {
    const formatted = new Intl.NumberFormat('en-IN', {
        style: 'currency',
        currency: 'INR',
        maximumFractionDigits: 0,
    }).format(data.today_rs);

    const preventableBadge = data.preventable_pct >= 50
        ? 'destructive'
        : data.preventable_pct >= 25
            ? 'secondary'
            : 'outline';

    return (
        <Card className="border-0 shadow-sm" style={{ backgroundColor: 'oklch(0.22 0.06 150)' }}>
            <CardContent className="p-4 flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <p className="text-xs font-semibold uppercase tracking-widest text-white/50 mb-1">
                        Today's Penalty Exposure
                    </p>
                    <p className="font-mono text-3xl font-bold text-white tabular-nums">
                        {formatted}
                    </p>
                </div>

                <div className="flex items-center gap-4">
                    {data.preventable_pct > 0 && (
                        <Badge variant={preventableBadge} className="text-xs">
                            {data.preventable_pct}% preventable
                        </Badge>
                    )}

                    {data.trend_7d.length > 1 && (
                        <div className="w-32 h-12">
                            <ResponsiveContainer width="100%" height="100%">
                                <AreaChart data={data.trend_7d}>
                                    <defs>
                                        <linearGradient id="penGrad" x1="0" y1="0" x2="0" y2="1">
                                            <stop offset="5%" stopColor="#C8A84B" stopOpacity={0.6} />
                                            <stop offset="95%" stopColor="#C8A84B" stopOpacity={0} />
                                        </linearGradient>
                                    </defs>
                                    <Area
                                        type="monotone"
                                        dataKey="rs"
                                        stroke="#C8A84B"
                                        strokeWidth={1.5}
                                        fill="url(#penGrad)"
                                        dot={false}
                                    />
                                    <Tooltip
                                        formatter={(v: number) =>
                                            new Intl.NumberFormat('en-IN', { style: 'currency', currency: 'INR', maximumFractionDigits: 0 }).format(v)
                                        }
                                        contentStyle={{ background: '#1E3A2F', border: 'none', color: '#fff', fontSize: 11 }}
                                    />
                                </AreaChart>
                            </ResponsiveContainer>
                        </div>
                    )}

                    <div className="text-right">
                        <p className="text-[10px] text-white/40 uppercase tracking-wide">7-day trend</p>
                        {data.trend_7d.length > 1 && (() => {
                            const last = data.trend_7d[data.trend_7d.length - 1]?.rs ?? 0;
                            const prev = data.trend_7d[data.trend_7d.length - 2]?.rs ?? 0;
                            const diff = last - prev;
                            const sign = diff >= 0 ? '+' : '';
                            return (
                                <p className={`font-mono text-sm font-semibold tabular-nums ${diff > 0 ? 'text-red-400' : 'text-green-400'}`}>
                                    {sign}{new Intl.NumberFormat('en-IN', { style: 'currency', currency: 'INR', maximumFractionDigits: 0 }).format(diff)}
                                </p>
                            );
                        })()}
                    </div>
                </div>
            </CardContent>
        </Card>
    );
}
```

- [ ] **Step 2: Verify TypeScript**

```bash
npm run build 2>&1 | grep -E "error|built"
```

- [ ] **Step 3: Commit**

```bash
git add resources/js/components/dashboard/penalty-exposure-strip.tsx
git commit -m "feat(dashboard): add PenaltyExposureStrip component"
```

---

### Task 6: Create `ActiveRakePipeline` component

**Files:**
- Create: `resources/js/components/dashboard/active-rake-pipeline.tsx`

- [ ] **Step 1: Create the component**

```tsx
import { Badge } from '@/components/ui/badge';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';

interface RakePipelineCard {
    rake_id: number;
    rake_number: string;
    siding_name: string;
    siding_code: string;
    wagon_count: number;
    overloaded_count: number;
    penalty_risk_rs: number;
    state: string;
    loading_date: string | null;
}

interface ActiveRakePipeline {
    loading: RakePipelineCard[];
    awaiting_clearance: RakePipelineCard[];
    dispatched: RakePipelineCard[];
}

function RakeCard({ card }: { card: RakePipelineCard }) {
    const hasOverload = card.overloaded_count > 0;
    const riskFormatted = new Intl.NumberFormat('en-IN', {
        style: 'currency', currency: 'INR', maximumFractionDigits: 0,
    }).format(card.penalty_risk_rs);

    return (
        <div className={`rounded-lg border p-3 text-sm transition-colors ${hasOverload ? 'border-red-200 bg-red-50' : 'border-gray-100 bg-white'}`}>
            <div className="flex items-start justify-between gap-2">
                <div>
                    <p className="font-mono font-semibold text-gray-900">{card.rake_number}</p>
                    <p className="text-[11px] text-gray-500">{card.siding_code} · {card.loading_date ?? '—'}</p>
                </div>
                {hasOverload && (
                    <Badge variant="destructive" className="text-[10px] shrink-0">
                        {card.overloaded_count} overloaded
                    </Badge>
                )}
            </div>
            <div className="mt-2 flex items-center justify-between text-[11px] text-gray-500">
                <span>{card.wagon_count} wagons</span>
                {hasOverload && (
                    <span className="font-mono font-semibold text-red-600 tabular-nums">{riskFormatted} risk</span>
                )}
            </div>
        </div>
    );
}

const COLUMNS = [
    { key: 'loading' as const, label: 'Loading', color: 'text-blue-600' },
    { key: 'awaiting_clearance' as const, label: 'Awaiting Clearance', color: 'text-amber-600' },
    { key: 'dispatched' as const, label: 'Dispatched Today', color: 'text-green-600' },
];

export function ActiveRakePipeline({ data }: { data: ActiveRakePipeline }) {
    return (
        <Card className="shadow-sm">
            <CardHeader className="pb-2">
                <CardTitle className="text-sm font-semibold uppercase tracking-wide" style={{ color: 'oklch(0.22 0.06 150)' }}>
                    Active Rake Pipeline
                </CardTitle>
            </CardHeader>
            <CardContent>
                <div className="grid grid-cols-3 gap-4">
                    {COLUMNS.map(col => (
                        <div key={col.key}>
                            <div className="mb-2 flex items-center justify-between">
                                <p className={`text-xs font-semibold uppercase tracking-wide ${col.color}`}>{col.label}</p>
                                <span className="rounded-full bg-gray-100 px-2 py-0.5 text-[10px] font-medium text-gray-600">
                                    {data[col.key].length}
                                </span>
                            </div>
                            <div className="flex flex-col gap-2">
                                {data[col.key].length === 0
                                    ? <p className="text-[11px] text-gray-400 italic">No rakes</p>
                                    : data[col.key].map(card => <RakeCard key={card.rake_id} card={card} />)
                                }
                            </div>
                        </div>
                    ))}
                </div>
            </CardContent>
        </Card>
    );
}
```

- [ ] **Step 2: Verify and commit**

```bash
npm run build 2>&1 | grep -E "error|built"
git add resources/js/components/dashboard/active-rake-pipeline.tsx
git commit -m "feat(dashboard): add ActiveRakePipeline kanban component"
```

---

### Task 7: Create `SidingCoalStock`, `SidingRiskScore`, `AlertFeed`, `DispatchSummary` components

**Files:**
- Create: `resources/js/components/dashboard/siding-coal-stock.tsx`
- Create: `resources/js/components/dashboard/siding-risk-score.tsx`
- Create: `resources/js/components/dashboard/alert-feed.tsx`
- Create: `resources/js/components/dashboard/dispatch-summary.tsx`

- [ ] **Step 1: Create `siding-coal-stock.tsx`**

```tsx
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';

interface SidingStock {
    siding_id: number;
    closing_balance_mt: number;
    dispatched_mt: number;
    last_receipt_at: string | null;
}

function daysOfStock(closingMt: number, dispatchedMt: number): number | null {
    if (dispatchedMt <= 0) return null;
    return Math.round(closingMt / dispatchedMt);
}

export function SidingCoalStock({ stocks }: { stocks: Record<number, SidingStock> }) {
    const entries = Object.values(stocks);

    return (
        <Card className="shadow-sm">
            <CardHeader className="pb-2">
                <CardTitle className="text-sm font-semibold uppercase tracking-wide" style={{ color: 'oklch(0.22 0.06 150)' }}>
                    Siding Coal Stock
                </CardTitle>
            </CardHeader>
            <CardContent>
                {entries.length === 0
                    ? <p className="text-sm text-gray-400">No stock data available.</p>
                    : (
                        <div className="divide-y divide-gray-100">
                            {entries.map(s => {
                                const days = daysOfStock(s.closing_balance_mt, s.dispatched_mt);
                                const daysColor = days === null ? 'text-gray-400' : days < 3 ? 'text-red-600' : days < 7 ? 'text-amber-600' : 'text-green-600';
                                return (
                                    <div key={s.siding_id} className="flex items-center justify-between py-2">
                                        <div>
                                            <p className="font-mono text-base font-semibold tabular-nums text-gray-900">
                                                {s.closing_balance_mt.toLocaleString('en-IN')} MT
                                            </p>
                                            <p className="text-[11px] text-gray-400">
                                                {s.last_receipt_at ? `Last receipt: ${s.last_receipt_at}` : 'No recent receipt'}
                                            </p>
                                        </div>
                                        {days !== null && (
                                            <div className="text-right">
                                                <p className={`font-mono text-xl font-bold tabular-nums ${daysColor}`}>{days}d</p>
                                                <p className="text-[10px] text-gray-400">stock</p>
                                            </div>
                                        )}
                                    </div>
                                );
                            })}
                        </div>
                    )
                }
            </CardContent>
        </Card>
    );
}
```

- [ ] **Step 2: Create `siding-risk-score.tsx`**

```tsx
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';

interface SidingRiskScoreData {
    siding_id: number;
    siding_name: string;
    siding_code: string;
    score: number;
    trend: string;
    risk_factors: string[];
    calculated_at: string | null;
}

function scoreColor(score: number): string {
    if (score >= 70) return 'text-red-600';
    if (score >= 30) return 'text-amber-600';
    return 'text-green-600';
}

function scoreBg(score: number): string {
    if (score >= 70) return 'bg-red-50 border-red-200';
    if (score >= 30) return 'bg-amber-50 border-amber-200';
    return 'bg-green-50 border-green-200';
}

export function SidingRiskScoreWidget({ scores }: { scores: Record<number, SidingRiskScoreData> }) {
    const entries = Object.values(scores);

    return (
        <Card className="shadow-sm">
            <CardHeader className="pb-2">
                <CardTitle className="text-sm font-semibold uppercase tracking-wide" style={{ color: 'oklch(0.22 0.06 150)' }}>
                    Siding Risk Score
                </CardTitle>
            </CardHeader>
            <CardContent>
                {entries.length === 0
                    ? <p className="text-sm text-gray-400">No risk data available.</p>
                    : (
                        <div className="flex flex-col gap-3">
                            {entries.map(s => (
                                <div key={s.siding_id} className={`rounded-lg border p-3 ${scoreBg(s.score)}`}>
                                    <div className="flex items-center justify-between">
                                        <div>
                                            <p className="font-semibold text-gray-900 text-sm">{s.siding_name}</p>
                                            <p className="text-[10px] text-gray-500">{s.siding_code}</p>
                                        </div>
                                        <div className="text-right">
                                            <p className={`font-mono text-2xl font-bold tabular-nums ${scoreColor(s.score)}`}>{s.score}</p>
                                            <p className="text-[10px] text-gray-400">/ 100</p>
                                        </div>
                                    </div>
                                    {s.risk_factors.length > 0 && (
                                        <ul className="mt-2 flex flex-wrap gap-1">
                                            {s.risk_factors.slice(0, 3).map((f, i) => (
                                                <li key={i} className="rounded text-[10px] bg-white/60 px-1.5 py-0.5 text-gray-600">{f}</li>
                                            ))}
                                        </ul>
                                    )}
                                </div>
                            ))}
                        </div>
                    )
                }
            </CardContent>
        </Card>
    );
}
```

- [ ] **Step 3: Create `alert-feed.tsx`**

```tsx
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Badge } from '@/components/ui/badge';

interface AlertRecord {
    id: number;
    type: string;
    title: string;
    body: string;
    severity: string;
    siding_id: number | null;
    rake_id: number | null;
    created_at: string;
}

const SEVERITY_STYLES: Record<string, string> = {
    critical: 'bg-red-50 border-red-200',
    high: 'bg-orange-50 border-orange-200',
    medium: 'bg-amber-50 border-amber-200',
    low: 'bg-blue-50 border-blue-200',
};

const SEVERITY_BADGE: Record<string, 'destructive' | 'secondary' | 'outline'> = {
    critical: 'destructive',
    high: 'destructive',
    medium: 'secondary',
    low: 'outline',
};

export function AlertFeed({ alerts }: { alerts: Record<string, AlertRecord[]> }) {
    const allAlerts = Object.entries(alerts)
        .sort(([a], [b]) => {
            const order = ['critical', 'high', 'medium', 'low'];
            return order.indexOf(a) - order.indexOf(b);
        })
        .flatMap(([, records]) => records);

    return (
        <Card className="shadow-sm">
            <CardHeader className="pb-2">
                <div className="flex items-center justify-between">
                    <CardTitle className="text-sm font-semibold uppercase tracking-wide" style={{ color: 'oklch(0.22 0.06 150)' }}>
                        Alert Feed
                    </CardTitle>
                    {allAlerts.length > 0 && (
                        <Badge variant="destructive" className="text-[10px]">{allAlerts.length}</Badge>
                    )}
                </div>
            </CardHeader>
            <CardContent>
                {allAlerts.length === 0
                    ? <p className="text-sm text-gray-400">No active alerts.</p>
                    : (
                        <div className="flex flex-col gap-2 max-h-64 overflow-y-auto pr-1">
                            {allAlerts.map(alert => (
                                <div key={alert.id} className={`rounded-lg border p-3 text-sm ${SEVERITY_STYLES[alert.severity] ?? 'bg-gray-50'}`}>
                                    <div className="flex items-start justify-between gap-2">
                                        <p className="font-medium text-gray-900 leading-snug">{alert.title}</p>
                                        <Badge variant={SEVERITY_BADGE[alert.severity] ?? 'outline'} className="text-[10px] shrink-0 capitalize">
                                            {alert.severity}
                                        </Badge>
                                    </div>
                                    {alert.body && (
                                        <p className="mt-1 text-[11px] text-gray-500 leading-relaxed">{alert.body}</p>
                                    )}
                                    <p className="mt-1 text-[10px] text-gray-400">{alert.created_at}</p>
                                </div>
                            ))}
                        </div>
                    )
                }
            </CardContent>
        </Card>
    );
}
```

- [ ] **Step 4: Create `dispatch-summary.tsx`**

```tsx
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';

interface SidingStock {
    siding_id: number;
    dispatched_mt: number;
    received_mt: number;
}

export function DispatchSummary({ stocks }: { stocks: Record<number, SidingStock> }) {
    const entries = Object.values(stocks);
    const totalDispatched = entries.reduce((sum, s) => sum + (s.dispatched_mt ?? 0), 0);
    const totalReceived   = entries.reduce((sum, s) => sum + (s.received_mt ?? 0), 0);

    return (
        <Card className="shadow-sm">
            <CardHeader className="pb-2">
                <CardTitle className="text-sm font-semibold uppercase tracking-wide" style={{ color: 'oklch(0.22 0.06 150)' }}>
                    Today's Dispatch
                </CardTitle>
            </CardHeader>
            <CardContent>
                <div className="grid grid-cols-2 gap-4">
                    <div>
                        <p className="text-[10px] text-gray-400 uppercase tracking-wide mb-1">Total Dispatched</p>
                        <p className="font-mono text-2xl font-bold tabular-nums text-gray-900">
                            {totalDispatched.toLocaleString('en-IN')}
                        </p>
                        <p className="text-[11px] text-gray-400">MT</p>
                    </div>
                    <div>
                        <p className="text-[10px] text-gray-400 uppercase tracking-wide mb-1">Total Received</p>
                        <p className="font-mono text-2xl font-bold tabular-nums text-gray-900">
                            {totalReceived.toLocaleString('en-IN')}
                        </p>
                        <p className="text-[11px] text-gray-400">MT</p>
                    </div>
                </div>
            </CardContent>
        </Card>
    );
}
```

- [ ] **Step 5: Build to verify all 4 components compile**

```bash
npm run build 2>&1 | grep -E "error|built"
```

- [ ] **Step 6: Commit all 4 components**

```bash
git add resources/js/components/dashboard/siding-coal-stock.tsx \
        resources/js/components/dashboard/siding-risk-score.tsx \
        resources/js/components/dashboard/alert-feed.tsx \
        resources/js/components/dashboard/dispatch-summary.tsx
git commit -m "feat(dashboard): add SidingCoalStock, SidingRiskScore, AlertFeed, DispatchSummary components"
```

---

### Task 8: Create `OperatorRakeWidget` component

**Files:**
- Create: `resources/js/components/dashboard/operator-rake-widget.tsx`

- [ ] **Step 1: Create the component**

```tsx
import { Card, CardContent } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { Link } from '@inertiajs/react';

interface OperatorRake {
    rake_id: number;
    rake_number: string;
    siding_name: string;
    state: string;
    loaded: number;
    total: number;
    status: string;
    loading_date: string | null;
}

export function OperatorRakeWidget({ rake }: { rake: OperatorRake | null }) {
    if (!rake) {
        return (
            <Card className="border-0 shadow-sm" style={{ backgroundColor: 'oklch(0.22 0.06 150)' }}>
                <CardContent className="p-6 text-center">
                    <p className="text-white/60 text-sm">No active rake assigned.</p>
                </CardContent>
            </Card>
        );
    }

    const pct = rake.total > 0 ? Math.round((rake.loaded / rake.total) * 100) : 0;

    return (
        <Card className="border-0 shadow-sm" style={{ backgroundColor: 'oklch(0.22 0.06 150)' }}>
            <CardContent className="p-6">
                <div className="flex items-start justify-between">
                    <div>
                        <p className="text-xs font-semibold uppercase tracking-widest text-white/50 mb-1">
                            Your Active Rake
                        </p>
                        <p className="font-mono text-2xl font-bold text-white">{rake.rake_number}</p>
                        <p className="text-sm text-white/60 mt-0.5">{rake.siding_name} · {rake.loading_date ?? '—'}</p>
                    </div>
                    <Button asChild className="btn-bgr-gold text-sm">
                        <Link href={`/rake-loader/rakes/${rake.rake_id}/loading`}>
                            Go to Loading →
                        </Link>
                    </Button>
                </div>

                <div className="mt-4">
                    <div className="flex justify-between text-xs text-white/60 mb-1">
                        <span>Loading progress</span>
                        <span className="font-mono tabular-nums">{rake.loaded} / {rake.total} wagons ({pct}%)</span>
                    </div>
                    <div className="h-2 rounded-full bg-white/20 overflow-hidden">
                        <div
                            className="h-full rounded-full transition-all"
                            style={{ width: `${pct}%`, backgroundColor: 'oklch(0.72 0.12 80)' }}
                        />
                    </div>
                </div>
            </CardContent>
        </Card>
    );
}
```

- [ ] **Step 2: Build and commit**

```bash
npm run build 2>&1 | grep -E "error|built"
git add resources/js/components/dashboard/operator-rake-widget.tsx
git commit -m "feat(dashboard): add OperatorRakeWidget component"
```

---

### Task 9: Wire Command Center section into `dashboard.tsx`

**Files:**
- Modify: `resources/js/pages/dashboard.tsx`

- [ ] **Step 1: Import the new components**

At the top of `dashboard.tsx`, after existing imports, add:

```tsx
import { PenaltyExposureStrip } from '@/components/dashboard/penalty-exposure-strip';
import { ActiveRakePipeline } from '@/components/dashboard/active-rake-pipeline';
import { SidingCoalStock } from '@/components/dashboard/siding-coal-stock';
import { SidingRiskScoreWidget } from '@/components/dashboard/siding-risk-score';
import { AlertFeed } from '@/components/dashboard/alert-feed';
import { DispatchSummary } from '@/components/dashboard/dispatch-summary';
import { OperatorRakeWidget } from '@/components/dashboard/operator-rake-widget';
```

- [ ] **Step 2: Extract new props inside the component function**

Inside the main dashboard component function, after existing prop destructuring, add:

```tsx
const penaltySummary    = props.penaltySummary;
const activeRakePipeline = props.activeRakePipeline;
const riskScores        = props.riskScores ?? {};
const alerts            = props.alerts ?? {};
const operatorRake      = props.operatorRake;
const sidingStocksMap   = props.sidingStocks ?? {};

const allowedWidgets    = props.allowedDashboardWidgets ?? [];
const isExecutive       = allowedWidgets.some(w =>
    ['penalty_exposure_command', 'rake_pipeline_command', 'siding_risk_score'].includes(w)
);
```

- [ ] **Step 3: Inject Command Center JSX**

Find the opening `<div>` or `<AppLayout>` wrapper in the dashboard component's `return (...)` statement and inject the Command Center section as the **first child**, before any existing content:

```tsx
{/* ── Command Center ── */}
<section className="mb-6 flex flex-col gap-4">
    <h2 className="text-xs font-bold uppercase tracking-widest" style={{ color: 'oklch(0.22 0.06 150)' }}>
        Operations Command Center
    </h2>

    {!isExecutive && (
        <OperatorRakeWidget rake={operatorRake ?? null} />
    )}

    {isExecutive && (
        <>
            {penaltySummary && (
                <PenaltyExposureStrip data={penaltySummary} />
            )}

            <div className="grid grid-cols-1 gap-4 lg:grid-cols-3">
                {activeRakePipeline && (
                    <div className="lg:col-span-2">
                        <ActiveRakePipeline data={activeRakePipeline} />
                    </div>
                )}
                <DispatchSummary stocks={sidingStocksMap} />
            </div>

            <div className="grid grid-cols-1 gap-4 lg:grid-cols-3">
                <SidingCoalStock stocks={sidingStocksMap} />
                <SidingRiskScoreWidget scores={riskScores} />
                <AlertFeed alerts={alerts} />
            </div>
        </>
    )}
</section>
{/* ── End Command Center ── */}
```

- [ ] **Step 4: Build and verify**

```bash
npm run build 2>&1 | tail -5
```

Expected: `✓ built in X.XXs`

- [ ] **Step 5: Commit**

```bash
git add resources/js/pages/dashboard.tsx
git commit -m "feat(dashboard): inject Command Center section with role-aware widgets"
```

---

### Task 10: Register new widget names in DashboardWidgetPermissions

**Files:**
- Read: `app/Http/Middleware/DashboardWidgetPermissions.php` (to understand current structure before editing)
- Modify: `app/Http/Middleware/DashboardWidgetPermissions.php`

- [ ] **Step 1: Read the existing file first**

Read `app/Http/Middleware/DashboardWidgetPermissions.php` to understand:
- How existing widget names are registered
- Which roles/permissions are mapped

- [ ] **Step 2: Add new widget names to the executive widget list**

Following the existing pattern in the file, add these widget names to the executive/manager allowed list:

```
'penalty_exposure_command'
'rake_pipeline_command'
'siding_risk_score'
'alert_feed_command'
'dispatch_summary_command'
```

And add this to the operator allowed list:
```
'operator_rake_command'
```

The exact code depends on the file structure (read it first in Step 1).

- [ ] **Step 3: Verify PHP parses correctly**

```bash
php artisan route:list --name=dashboard 2>&1 | head -5
```

- [ ] **Step 4: Commit**

```bash
git add app/Http/Middleware/DashboardWidgetPermissions.php
git commit -m "feat(dashboard): register Phase 2 Command Center widget permissions"
```

---

### Task 11: Final build and smoke test

**Files:** None (test only)

- [ ] **Step 1: Full production build**

```bash
npm run build 2>&1 | tail -10
```

Expected: `✓ built in X.XXs` with zero errors.

- [ ] **Step 2: PHP syntax check**

```bash
php artisan route:list --name=dashboard 2>&1 | head -10
php artisan config:clear && php artisan cache:clear 2>&1
```

- [ ] **Step 3: Verify dashboard loads without exception**

```bash
php artisan tinker --execute 'echo App\Http\Controllers\Dashboard\ExecutiveDashboardController::class;'
```

Expected: prints the class name (no errors = no syntax problems).

- [ ] **Step 4: Commit summary**

```bash
git log --oneline -8
```

Verify 8 Phase 2 commits are present.

---

## Summary

| Widget | Backend prop | Component | Task |
|--------|-------------|-----------|------|
| Penalty Exposure Strip | `penaltySummary` | `penalty-exposure-strip.tsx` | 1 + 5 |
| Active Rake Pipeline | `activeRakePipeline` | `active-rake-pipeline.tsx` | 2 + 6 |
| Siding Coal Stock | `sidingStocks` (existing, enriched) | `siding-coal-stock.tsx` | 7 |
| Siding Risk Score | `riskScores` | `siding-risk-score.tsx` | 3 + 7 |
| Alert Feed | `alerts` | `alert-feed.tsx` | 3 + 7 |
| Dispatch Summary | `sidingStocks` (existing) | `dispatch-summary.tsx` | 7 |
| Operator Rake | `operatorRake` | `operator-rake-widget.tsx` | 3 + 8 |

All existing dashboard props and functionality preserved. New Command Center section injected at top, hidden below existing charts.
