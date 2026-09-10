# Phase 3: Loading Workflow Intelligence — Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Add real-time PCC violation gauges, live penalty panel, and advisory override logging to the wagon loading page. Core gauge logic is 100% frontend — all required data is already in Inertia props.

**Architecture:**
- Backend (additive): `loading_overrides` table + `LoadingOverride` model + `LogLoadingOverride` action + one new POST route
- Frontend: Pure utility functions + 5 new components + minimal modifications to existing `WagonLoadingWorkflow` (add one callback prop + one gauge column) + `loading.tsx` layout change to 2-col with penalty panel

**Tech Stack:** Laravel 13, Pest, Inertia v3, React 19, Tailwind v4, shadcn/ui

---

## File Map

| File | Action |
|------|--------|
| `database/migrations/TIMESTAMP_create_loading_overrides_table.php` | Create |
| `app/Models/LoadingOverride.php` | Create |
| `app/Actions/LogLoadingOverride.php` | Create |
| `app/Http/Controllers/Rakes/RakeLoaderController.php` | Modify — add `storeOverride()` method |
| `routes/web.php` | Modify — add POST override route |
| `resources/js/utils/pcc.ts` | Create |
| `resources/js/components/loading/PccGaugeBar.tsx` | Create |
| `resources/js/components/loading/PccStatusPills.tsx` | Create |
| `resources/js/components/loading/PenaltyIntelligencePanel.tsx` | Create |
| `resources/js/components/loading/ManagerClearanceModal.tsx` | Create |
| `resources/js/components/rakes/workflow/WagonLoadingWorkflow.tsx` | Modify — add callback + gauge column |
| `resources/js/pages/rake-loader/loading.tsx` | Modify — 2-col layout + penalty panel |

---

### Task 1: Create `loading_overrides` migration and model

**Files:**
- Create: `database/migrations/TIMESTAMP_create_loading_overrides_table.php`
- Create: `app/Models/LoadingOverride.php`

- [ ] **Step 1: Generate migration**

```bash
php artisan make:migration create_loading_overrides_table --no-interaction
```

- [ ] **Step 2: Write migration content**

Open the generated migration file and replace the `up()` method with:

```php
public function up(): void
{
    Schema::create('loading_overrides', function (Blueprint $table) {
        $table->id();
        $table->foreignId('rake_id')->constrained()->cascadeOnDelete();
        $table->foreignId('wagon_loading_id')->nullable()->constrained('wagon_loading')->nullOnDelete();
        $table->foreignId('operator_id')->constrained('users')->cascadeOnDelete();
        $table->enum('reason', ['reduced_load', 'equipment_constraint', 'railway_instruction', 'other']);
        $table->text('notes')->nullable();
        $table->decimal('overload_mt', 8, 3)->default(0);
        $table->decimal('estimated_penalty_at_time', 10, 2)->default(0);
        $table->timestamps();
    });
}

public function down(): void
{
    Schema::dropIfExists('loading_overrides');
}
```

- [ ] **Step 3: Run migration**

```bash
php artisan migrate --no-interaction 2>&1 | tail -5
```

Expected: `loading_overrides` table created.

- [ ] **Step 4: Create Model**

```bash
php artisan make:model LoadingOverride --no-interaction
```

Replace model content with:

```php
<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class LoadingOverride extends Model
{
    protected $fillable = [
        'rake_id',
        'wagon_loading_id',
        'operator_id',
        'reason',
        'notes',
        'overload_mt',
        'estimated_penalty_at_time',
    ];

    protected function casts(): array
    {
        return [
            'overload_mt'               => 'decimal:3',
            'estimated_penalty_at_time' => 'decimal:2',
        ];
    }

    public function rake(): BelongsTo
    {
        return $this->belongsTo(Rake::class);
    }

    public function operator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'operator_id');
    }

    public function wagonLoading(): BelongsTo
    {
        return $this->belongsTo(WagonLoading::class);
    }
}
```

- [ ] **Step 5: Verify**

```bash
php artisan db:show --table=loading_overrides 2>&1 | head -20
```

- [ ] **Step 6: Commit**

```bash
git add database/migrations/ app/Models/LoadingOverride.php
git commit -m "feat(phase3): add loading_overrides migration and model"
```

---

### Task 2: Create `LogLoadingOverride` action and POST route

**Files:**
- Create: `app/Actions/LogLoadingOverride.php`
- Modify: `app/Http/Controllers/Rakes/RakeLoaderController.php`
- Modify: `routes/web.php`

- [ ] **Step 1: Create Action**

```bash
php artisan make:action LogLoadingOverride --no-interaction
```

Replace content with:

```php
<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\LoadingOverride;
use App\Models\Rake;
use App\Models\User;

final readonly class LogLoadingOverride
{
    public function handle(
        Rake $rake,
        User $operator,
        string $reason,
        float $overloadMt,
        float $estimatedPenaltyRs,
        ?string $notes = null,
        ?int $wagonLoadingId = null,
    ): LoadingOverride {
        return LoadingOverride::create([
            'rake_id'                   => $rake->id,
            'wagon_loading_id'          => $wagonLoadingId,
            'operator_id'               => $operator->id,
            'reason'                    => $reason,
            'notes'                     => $notes,
            'overload_mt'               => $overloadMt,
            'estimated_penalty_at_time' => $estimatedPenaltyRs,
        ]);
    }
}
```

- [ ] **Step 2: Add `storeOverride()` to RakeLoaderController**

Read the file first to find the correct location (after the `loading()` method). Then add:

```php
public function storeOverride(Request $request, Rake $rake): \Illuminate\Http\JsonResponse
{
    $validated = $request->validate([
        'reason'               => ['required', 'in:reduced_load,equipment_constraint,railway_instruction,other'],
        'notes'                => ['nullable', 'string', 'max:500'],
        'overload_mt'          => ['required', 'numeric', 'min:0'],
        'estimated_penalty_rs' => ['required', 'numeric', 'min:0'],
        'wagon_loading_id'     => ['nullable', 'integer', 'exists:wagon_loading,id'],
    ]);

    /** @var \App\Models\User $user */
    $user = $request->user();

    (new \App\Actions\LogLoadingOverride)->handle(
        rake: $rake,
        operator: $user,
        reason: $validated['reason'],
        overloadMt: (float) $validated['overload_mt'],
        estimatedPenaltyRs: (float) $validated['estimated_penalty_rs'],
        notes: $validated['notes'] ?? null,
        wagonLoadingId: $validated['wagon_loading_id'] ?? null,
    );

    return response()->json(['logged' => true]);
}
```

- [ ] **Step 3: Add route**

In `routes/web.php`, find the rake-loader route group and add:

```php
Route::post('/rake-loader/rakes/{rake}/override', [RakeLoaderController::class, 'storeOverride'])
    ->name('rake-loader.rakes.override');
```

- [ ] **Step 4: Run Pint**

```bash
vendor/bin/pint --dirty --format agent
```

- [ ] **Step 5: Verify routes**

```bash
php artisan route:list --name=rake-loader.rakes.override 2>&1
```

Expected: POST route listed.

- [ ] **Step 6: Commit**

```bash
git add app/Actions/LogLoadingOverride.php app/Http/Controllers/Rakes/RakeLoaderController.php routes/web.php
git commit -m "feat(phase3): add LogLoadingOverride action and POST override route"
```

---

### Task 3: Create PCC utility functions

**Files:**
- Create: `resources/js/utils/pcc.ts`

- [ ] **Step 1: Create the utility file**

```typescript
export const PENALTY_RATE_PER_EXCESS_MT = 60; // ₹ per excess MT (Indian Railways)

export type PccStatus = 'over' | 'near' | 'ok' | 'empty';

export interface WagonPccState {
    wagonId: number;
    wagonNumber: string;
    pccMt: number;
    loadedMt: number;
    pct: number;
    status: PccStatus;
    excessMt: number;
    penaltyRs: number;
}

export function parseMt(value: string | number | null | undefined): number {
    const n = parseFloat(String(value ?? '0'));
    return isNaN(n) ? 0 : n;
}

export function calcPccPct(loadedMt: number, pccMt: number): number {
    if (pccMt <= 0) return 0;
    return (loadedMt / pccMt) * 100;
}

export function calcStatus(loadedMt: number, pccMt: number): PccStatus {
    if (loadedMt <= 0) return 'empty';
    const pct = calcPccPct(loadedMt, pccMt);
    if (pct >= 100) return 'over';
    if (pct >= 90) return 'near';
    return 'ok';
}

export function calcPenaltyRs(loadedMt: number, pccMt: number): number {
    if (loadedMt <= pccMt || pccMt <= 0) return 0;
    const excessMt = loadedMt - pccMt;
    return Math.round(excessMt * PENALTY_RATE_PER_EXCESS_MT * 100) / 100;
}

export function buildWagonPccState(
    wagonId: number,
    wagonNumber: string,
    pccMtRaw: string | number | null | undefined,
    loadedMtRaw: string | number | null | undefined,
): WagonPccState {
    const pccMt = parseMt(pccMtRaw);
    const loadedMt = parseMt(loadedMtRaw);
    const pct = calcPccPct(loadedMt, pccMt);
    const status = calcStatus(loadedMt, pccMt);
    const excessMt = Math.max(0, loadedMt - pccMt);
    const penaltyRs = calcPenaltyRs(loadedMt, pccMt);
    return { wagonId, wagonNumber, pccMt, loadedMt, pct, status, excessMt, penaltyRs };
}

export function summarisePccStates(states: WagonPccState[]): {
    ok: number;
    near: number;
    over: number;
    empty: number;
    totalPenaltyRs: number;
    totalExcessMt: number;
} {
    return states.reduce(
        (acc, s) => ({
            ok:             acc.ok + (s.status === 'ok' ? 1 : 0),
            near:           acc.near + (s.status === 'near' ? 1 : 0),
            over:           acc.over + (s.status === 'over' ? 1 : 0),
            empty:          acc.empty + (s.status === 'empty' ? 1 : 0),
            totalPenaltyRs: acc.totalPenaltyRs + s.penaltyRs,
            totalExcessMt:  acc.totalExcessMt + s.excessMt,
        }),
        { ok: 0, near: 0, over: 0, empty: 0, totalPenaltyRs: 0, totalExcessMt: 0 },
    );
}
```

- [ ] **Step 2: Build to verify TypeScript**

```bash
npm run build 2>&1 | grep -E "error|built"
```

- [ ] **Step 3: Commit**

```bash
git add resources/js/utils/pcc.ts
git commit -m "feat(phase3): add PCC calculation utility functions"
```

---

### Task 4: Create `PccGaugeBar` and `PccStatusPills` components

**Files:**
- Create: `resources/js/components/loading/PccGaugeBar.tsx`
- Create: `resources/js/components/loading/PccStatusPills.tsx`

- [ ] **Step 1: Create `PccGaugeBar.tsx`**

```tsx
interface PccGaugeBarProps {
    pct: number;       // 0–100+ (can exceed 100 when overloaded)
    loadedMt: number;
    pccMt: number;
}

function barColor(pct: number): string {
    if (pct >= 100) return '#dc2626'; // red
    if (pct >= 90) return '#d97706';  // amber
    return '#16a34a';                  // green
}

export function PccGaugeBar({ pct, loadedMt, pccMt }: PccGaugeBarProps) {
    const clampedPct = Math.min(pct, 100);
    const color = barColor(pct);
    const label = pccMt > 0 ? `${pct.toFixed(1)}%` : '—';

    return (
        <div className="flex flex-col gap-0.5">
            <div className="h-2 w-full overflow-hidden rounded-full bg-gray-100">
                <div
                    className="h-full rounded-full transition-all duration-150"
                    style={{ width: `${clampedPct}%`, backgroundColor: color }}
                />
            </div>
            <span className="font-mono text-[10px] tabular-nums" style={{ color }}>
                {label}
            </span>
        </div>
    );
}
```

- [ ] **Step 2: Create `PccStatusPills.tsx`**

```tsx
interface PccStatusPillsProps {
    ok: number;
    near: number;
    over: number;
    empty: number;
}

export function PccStatusPills({ ok, near, over, empty }: PccStatusPillsProps) {
    return (
        <div className="flex flex-wrap items-center gap-2 text-xs font-semibold">
            {ok > 0 && (
                <span className="flex items-center gap-1 rounded-full bg-green-100 px-2.5 py-1 text-green-700">
                    ✓ {ok} OK
                </span>
            )}
            {near > 0 && (
                <span className="flex items-center gap-1 rounded-full bg-amber-100 px-2.5 py-1 text-amber-700">
                    ⚡ {near} Near
                </span>
            )}
            {over > 0 && (
                <span className="flex items-center gap-1 rounded-full bg-red-100 px-2.5 py-1 text-red-700">
                    ⚠ {over} Over
                </span>
            )}
            {empty > 0 && (
                <span className="flex items-center gap-1 rounded-full bg-gray-100 px-2.5 py-1 text-gray-500">
                    ○ {empty} Empty
                </span>
            )}
        </div>
    );
}
```

- [ ] **Step 3: Build and commit**

```bash
npm run build 2>&1 | grep -E "error|built"
git add resources/js/components/loading/PccGaugeBar.tsx resources/js/components/loading/PccStatusPills.tsx
git commit -m "feat(phase3): add PccGaugeBar and PccStatusPills components"
```

---

### Task 5: Create `PenaltyIntelligencePanel` component

**Files:**
- Create: `resources/js/components/loading/PenaltyIntelligencePanel.tsx`

- [ ] **Step 1: Create the component**

```tsx
import type { WagonPccState } from '@/utils/pcc';

interface PenaltyIntelligencePanelProps {
    states: WagonPccState[];
    summary: {
        ok: number;
        near: number;
        over: number;
        empty: number;
        totalPenaltyRs: number;
        totalExcessMt: number;
    };
    onRequestClearance: () => void;
}

const inr = (v: number) =>
    new Intl.NumberFormat('en-IN', { style: 'currency', currency: 'INR', maximumFractionDigits: 0 }).format(v);

export function PenaltyIntelligencePanel({
    states,
    summary,
    onRequestClearance,
}: PenaltyIntelligencePanelProps) {
    const overWagons = states.filter((s) => s.status === 'over');

    return (
        <div
            className="flex h-full flex-col gap-4 rounded-xl p-4 text-white"
            style={{ backgroundColor: 'oklch(0.22 0.06 150)' }}
        >
            {/* Penalty total */}
            <div>
                <p className="text-[10px] font-semibold uppercase tracking-widest text-white/50">
                    Estimated Penalty
                </p>
                <p className="font-mono text-3xl font-bold tabular-nums">
                    {inr(summary.totalPenaltyRs)}
                </p>
                {summary.totalExcessMt > 0 && (
                    <p className="mt-0.5 font-mono text-xs tabular-nums text-white/60">
                        {summary.totalExcessMt.toFixed(3)} MT excess
                    </p>
                )}
            </div>

            {/* Status grid */}
            <div className="grid grid-cols-2 gap-2">
                {[
                    { label: 'Within PCC', count: summary.ok + summary.near, color: 'bg-green-900/40 text-green-300' },
                    { label: 'Over PCC',   count: summary.over,              color: 'bg-red-900/40 text-red-300' },
                    { label: 'Near Limit', count: summary.near,              color: 'bg-amber-900/40 text-amber-300' },
                    { label: 'Not Loaded', count: summary.empty,             color: 'bg-white/10 text-white/50' },
                ].map((item) => (
                    <div key={item.label} className={`rounded-lg p-2 ${item.color}`}>
                        <p className="font-mono text-xl font-bold tabular-nums">{item.count}</p>
                        <p className="text-[10px] font-medium leading-tight">{item.label}</p>
                    </div>
                ))}
            </div>

            {/* Problem wagon list */}
            {overWagons.length > 0 && (
                <div>
                    <p className="mb-1.5 text-[10px] font-semibold uppercase tracking-wide text-white/50">
                        Over-PCC Wagons
                    </p>
                    <div className="flex max-h-48 flex-col gap-1.5 overflow-y-auto pr-1">
                        {overWagons.map((w) => (
                            <div key={w.wagonId} className="rounded-lg bg-red-900/30 p-2">
                                <div className="flex items-center justify-between">
                                    <p className="font-mono text-xs font-semibold">{w.wagonNumber}</p>
                                    <p className="font-mono text-xs font-semibold tabular-nums text-red-300">
                                        {inr(w.penaltyRs)}
                                    </p>
                                </div>
                                <div className="mt-1 h-1.5 overflow-hidden rounded-full bg-white/10">
                                    <div
                                        className="h-full rounded-full bg-red-500"
                                        style={{ width: `${Math.min(w.pct, 100)}%` }}
                                    />
                                </div>
                                <p className="mt-0.5 font-mono text-[10px] tabular-nums text-red-300">
                                    +{w.excessMt.toFixed(3)} MT over limit
                                </p>
                            </div>
                        ))}
                    </div>
                </div>
            )}

            {/* AI suggestion stub */}
            <div className="rounded-lg bg-white/5 p-3 text-xs text-white/60">
                <p className="mb-1 font-semibold text-white/80">💡 Suggestion</p>
                {summary.over === 0
                    ? 'All wagons within PCC. Ready to dispatch.'
                    : `Redistribute ${summary.totalExcessMt.toFixed(2)} MT excess across ${summary.over} wagon${summary.over > 1 ? 's' : ''} to eliminate penalty exposure.`
                }
            </div>

            {/* Clearance button */}
            <div className="mt-auto">
                <button
                    type="button"
                    onClick={onRequestClearance}
                    className="btn-bgr-gold w-full rounded-lg px-4 py-2.5 text-sm font-semibold transition-opacity hover:opacity-90"
                >
                    Request Manager Clearance
                </button>
            </div>
        </div>
    );
}
```

- [ ] **Step 2: Build and commit**

```bash
npm run build 2>&1 | grep -E "error|built"
git add resources/js/components/loading/PenaltyIntelligencePanel.tsx
git commit -m "feat(phase3): add PenaltyIntelligencePanel component"
```

---

### Task 6: Create `ManagerClearanceModal` component

**Files:**
- Create: `resources/js/components/loading/ManagerClearanceModal.tsx`

- [ ] **Step 1: Create the component**

```tsx
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Textarea } from '@/components/ui/textarea';
import { useState } from 'react';

const REASONS = [
    { value: 'reduced_load',         label: 'Reduced load (remediated)' },
    { value: 'equipment_constraint', label: 'Equipment constraint' },
    { value: 'railway_instruction',  label: 'Railway instruction' },
    { value: 'other',                label: 'Other' },
] as const;

interface ManagerClearanceModalProps {
    open: boolean;
    onOpenChange: (open: boolean) => void;
    rakeId: number;
    overloadMt: number;
    estimatedPenaltyRs: number;
    onConfirmed: () => void;
}

export function ManagerClearanceModal({
    open,
    onOpenChange,
    rakeId,
    overloadMt,
    estimatedPenaltyRs,
    onConfirmed,
}: ManagerClearanceModalProps) {
    const [reason, setReason] = useState<string>('');
    const [notes, setNotes] = useState('');
    const [loading, setLoading] = useState(false);

    const inr = (v: number) =>
        new Intl.NumberFormat('en-IN', { style: 'currency', currency: 'INR', maximumFractionDigits: 0 }).format(v);

    function handleConfirm() {
        if (!reason) return;
        setLoading(true);

        const token = (document.cookie.match(/XSRF-TOKEN=([^;]+)/) ?? [])[1];
        const csrfToken = token ? decodeURIComponent(token) : '';

        fetch(`/rake-loader/rakes/${rakeId}/override`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-XSRF-TOKEN': csrfToken,
                Accept: 'application/json',
            },
            body: JSON.stringify({
                reason,
                notes: notes || null,
                overload_mt: overloadMt,
                estimated_penalty_rs: estimatedPenaltyRs,
            }),
        })
            .then(() => {
                setLoading(false);
                onOpenChange(false);
                setReason('');
                setNotes('');
                onConfirmed();
            })
            .catch(() => setLoading(false));
    }

    return (
        <Dialog open={open} onOpenChange={onOpenChange}>
            <DialogContent className="max-w-md">
                <DialogHeader>
                    <DialogTitle>Request Manager Clearance</DialogTitle>
                    <DialogDescription>
                        {overloadMt > 0
                            ? `${overloadMt.toFixed(3)} MT excess load detected. Estimated penalty: ${inr(estimatedPenaltyRs)}. Provide a reason to proceed.`
                            : 'Provide a reason to request manager clearance.'}
                    </DialogDescription>
                </DialogHeader>

                <div className="flex flex-col gap-4 py-2">
                    <div className="flex flex-col gap-1.5">
                        <Label htmlFor="reason">Reason *</Label>
                        <Select value={reason} onValueChange={setReason}>
                            <SelectTrigger id="reason">
                                <SelectValue placeholder="Select a reason..." />
                            </SelectTrigger>
                            <SelectContent>
                                {REASONS.map((r) => (
                                    <SelectItem key={r.value} value={r.value}>
                                        {r.label}
                                    </SelectItem>
                                ))}
                            </SelectContent>
                        </Select>
                    </div>

                    <div className="flex flex-col gap-1.5">
                        <Label htmlFor="notes">Notes (optional)</Label>
                        <Textarea
                            id="notes"
                            value={notes}
                            onChange={(e) => setNotes(e.target.value)}
                            placeholder="Additional context..."
                            rows={3}
                        />
                    </div>
                </div>

                <DialogFooter>
                    <Button variant="outline" onClick={() => onOpenChange(false)} disabled={loading}>
                        Cancel
                    </Button>
                    <Button
                        onClick={handleConfirm}
                        disabled={!reason || loading}
                        className="btn-bgr-gold"
                    >
                        {loading ? 'Logging...' : 'Confirm & Proceed'}
                    </Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>
    );
}
```

- [ ] **Step 2: Build and commit**

```bash
npm run build 2>&1 | grep -E "error|built"
git add resources/js/components/loading/ManagerClearanceModal.tsx
git commit -m "feat(phase3): add ManagerClearanceModal with override logging"
```

---

### Task 7: Modify `WagonLoadingWorkflow.tsx` to emit live qty changes and add PCC gauge column

**Files:**
- Modify: `resources/js/components/rakes/workflow/WagonLoadingWorkflow.tsx`

- [ ] **Step 1: Read the component**

Read `resources/js/components/rakes/workflow/WagonLoadingWorkflow.tsx` to find:
1. The prop interface (where to add `onLoadedQtyChange`)
2. The `loaded_quantity_mt` input element (where to call the callback)
3. The table row element (where to add row background tinting)
4. Whether there's already a "Load vs PCC" or gauge column

- [ ] **Step 2: Add callback to the prop type**

Find the existing prop type/interface for the component. Add:
```typescript
onLoadedQtyChange?: (wagonId: number, loadedMt: number) => void;
```

- [ ] **Step 3: Call callback when loaded_quantity_mt input changes**

Find the `onChange` handler for the `loaded_quantity_mt` input. Add a call to the callback:

```typescript
// Inside the onChange handler for loaded_quantity_mt, after updating local state:
onLoadedQtyChange?.(wagonId, parseFloat(newValue) || 0);
```

The exact code depends on the current implementation — read carefully before editing.

- [ ] **Step 4: Add row background tinting**

Find where each wagon row `<tr>` (or row container) is rendered. Add conditional background class:

```typescript
import { calcStatus, parseMt } from '@/utils/pcc';

// In the row render, compute the status for tinting:
const pccMt = parseMt(wagon.pcc_weight_mt);
const loadedMt = parseMt(wl?.loaded_quantity_mt);
const rowStatus = pccMt > 0 ? calcStatus(loadedMt, pccMt) : 'empty';
const rowBg = rowStatus === 'over' ? 'bg-[#fff5f5]' : rowStatus === 'near' ? 'bg-[#fffdf0]' : '';
```

Apply `rowBg` to the row's className.

- [ ] **Step 5: Add PCC gauge column**

In the table header, after the "Loaded (MT)" column header, add:
```tsx
<th className="px-2 py-1 text-[10px] font-semibold uppercase tracking-wide text-gray-500">
    Load vs PCC
</th>
```

In the table body row, after the loaded MT cell, add:
```tsx
import { PccGaugeBar } from '@/components/loading/PccGaugeBar';
import { calcPccPct } from '@/utils/pcc';
// ...
<td className="px-2 py-1 min-w-[100px]">
    {pccMt > 0 ? (
        <PccGaugeBar pct={calcPccPct(loadedMt, pccMt)} loadedMt={loadedMt} pccMt={pccMt} />
    ) : (
        <span className="text-[11px] text-gray-400">No PCC</span>
    )}
</td>
```

**IMPORTANT:** The exact location of these insertions depends on the current table structure. Read the file carefully. If the component is very complex or uses a different rendering pattern (virtualized list, etc.), adapt accordingly. Do NOT break the existing functionality — only add columns and callback.

- [ ] **Step 6: Build and verify**

```bash
npm run build 2>&1 | tail -5
```

Fix any TypeScript errors before committing.

- [ ] **Step 7: Commit**

```bash
git add resources/js/components/rakes/workflow/WagonLoadingWorkflow.tsx
git commit -m "feat(phase3): add PCC gauge column and live qty callback to WagonLoadingWorkflow"
```

---

### Task 8: Wire loading.tsx — 2-col layout with penalty panel and clearance modal

**Files:**
- Modify: `resources/js/pages/rake-loader/loading.tsx`

- [ ] **Step 1: Read loading.tsx**

Read the file to understand:
1. Current component structure and prop types
2. Where `WagonLoadingWorkflow` is rendered
3. How the rake data is accessed (`props.rake` or destructured)

- [ ] **Step 2: Add imports**

Add at the top of the file (after existing imports):

```tsx
import { ManagerClearanceModal } from '@/components/loading/ManagerClearanceModal';
import { PccStatusPills } from '@/components/loading/PccStatusPills';
import { PenaltyIntelligencePanel } from '@/components/loading/PenaltyIntelligencePanel';
import { buildWagonPccState, summarisePccStates } from '@/utils/pcc';
import type { WagonPccState } from '@/utils/pcc';
import { useCallback, useMemo, useState } from 'react';
```

- [ ] **Step 3: Add state inside the component function**

After the existing props/state setup in the component:

```tsx
// PCC live state: wagonId → loadedMt
const [liveMtMap, setLiveMtMap] = useState<Record<number, number>>(() => {
    const map: Record<number, number> = {};
    (rake.wagonLoadings ?? []).forEach((wl) => {
        map[wl.wagon_id] = parseFloat(wl.loaded_quantity_mt) || 0;
    });
    return map;
});

const [clearanceModalOpen, setClearanceModalOpen] = useState(false);

const handleLoadedQtyChange = useCallback((wagonId: number, loadedMt: number) => {
    setLiveMtMap((prev) => ({ ...prev, [wagonId]: loadedMt }));
}, []);

const pccStates: WagonPccState[] = useMemo(() => {
    return (rake.wagons ?? []).map((wagon) => {
        const loadedMt = liveMtMap[wagon.id] ?? 0;
        return buildWagonPccState(
            wagon.id,
            wagon.wagon_number,
            wagon.pcc_weight_mt,
            loadedMt,
        );
    });
}, [rake.wagons, liveMtMap]);

const pccSummary = useMemo(() => summarisePccStates(pccStates), [pccStates]);
```

- [ ] **Step 4: Wrap the loading layout in 2-col grid**

Find where `<WagonLoadingWorkflow .../>` is rendered. Replace the surrounding container with:

```tsx
{/* Toolbar pills */}
<div className="mb-3">
    <PccStatusPills
        ok={pccSummary.ok}
        near={pccSummary.near}
        over={pccSummary.over}
        empty={pccSummary.empty}
    />
</div>

{/* 2-col layout: workflow + penalty panel */}
<div className="flex gap-4">
    <div className="min-w-0 flex-1">
        <WagonLoadingWorkflow
            {/* ... all existing props preserved unchanged ... */}
            onLoadedQtyChange={handleLoadedQtyChange}
        />
    </div>
    <div className="w-72 shrink-0">
        <PenaltyIntelligencePanel
            states={pccStates}
            summary={pccSummary}
            onRequestClearance={() => setClearanceModalOpen(true)}
        />
    </div>
</div>

{/* Clearance modal */}
<ManagerClearanceModal
    open={clearanceModalOpen}
    onOpenChange={setClearanceModalOpen}
    rakeId={rake.id}
    overloadMt={pccSummary.totalExcessMt}
    estimatedPenaltyRs={pccSummary.totalPenaltyRs}
    onConfirmed={() => {
        // Override logged — form still submits normally
    }}
/>
```

**IMPORTANT:** Keep all existing props on `WagonLoadingWorkflow` unchanged. Only ADD `onLoadedQtyChange`. The exact existing props depend on what you see when reading the file.

- [ ] **Step 5: Build and verify**

```bash
npm run build 2>&1 | tail -5
```

Fix TypeScript errors. Common issue: if `rake` is typed as `RakeHydrated`, ensure the type includes `wagonLoadings` with `wagon_id`.

- [ ] **Step 6: Commit**

```bash
git add resources/js/pages/rake-loader/loading.tsx
git commit -m "feat(phase3): add PCC toolbar pills, penalty panel, and clearance modal to loading page"
```

---

### Task 9: Final build and smoke test

- [ ] **Step 1: Full production build**

```bash
npm run build 2>&1 | tail -8
```

Expected: `✓ built in X.XXs` with zero errors.

- [ ] **Step 2: PHP checks**

```bash
php artisan route:list --name=rake-loader 2>&1 | head -10
php artisan migrate:status 2>&1 | grep loading_overrides
```

Expected: override route listed, loading_overrides migration shows `Ran`.

- [ ] **Step 3: Commit log**

```bash
git log --oneline -9
```

Verify 9 Phase 3 commits present.

---

## Summary

| Feature | Files | Task |
|---------|-------|------|
| DB + Model | migration, LoadingOverride.php | 1 |
| Override API | LogLoadingOverride, RakeLoaderController, routes | 2 |
| PCC utilities | `utils/pcc.ts` | 3 |
| Gauge + pills | PccGaugeBar, PccStatusPills | 4 |
| Penalty panel | PenaltyIntelligencePanel | 5 |
| Clearance modal | ManagerClearanceModal | 6 |
| Workflow PCC column | WagonLoadingWorkflow (modified) | 7 |
| Loading page wiring | loading.tsx | 8 |
