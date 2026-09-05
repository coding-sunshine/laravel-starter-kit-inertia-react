# Real-Time Siding Monitor Dashboard — Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Build a real-time siding monitor page showing an animated wagon train, countdown timer, live weight ring, and overload alert feed — all driven by Reverb WebSocket events and animated with Framer Motion.

**Architecture:** Inertia page at `sidings/{siding}/monitor` serves initial data. Reverb events write into a per-siding Zustand store. React components subscribe to their own slice only — surgical re-renders, Framer Motion animations fire only on real data changes.

**Tech Stack:** React 19, Inertia v3, Framer Motion, Zustand, Laravel Reverb, Tailwind v4, Vitest, Pest 4

---

### Task 1: Install Zustand

**Files:**
- Modify: `package.json`

- [ ] **Step 1: Install Zustand**

```bash
npm install zustand
```

- [ ] **Step 2: Verify install**

```bash
node -e "require('zustand'); console.log('zustand ok')"
```

Expected: `zustand ok`

- [ ] **Step 3: Commit**

```bash
git add package.json package-lock.json
git commit -m "feat: install zustand for real-time siding store"
```

---

### Task 2: Broadcast events — `WagonWeightUpdated` and `RakeStatusUpdated`

**Files:**
- Create: `app/Events/WagonWeightUpdated.php`
- Create: `app/Events/RakeStatusUpdated.php`
- Modify: `routes/channels.php`

- [ ] **Step 1: Create `WagonWeightUpdated`**

Create `app/Events/WagonWeightUpdated.php`:

```php
<?php

declare(strict_types=1);

namespace App\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

final class WagonWeightUpdated implements ShouldBroadcast
{
    use Dispatchable;
    use InteractsWithSockets;
    use SerializesModels;

    public function __construct(
        public readonly int $sidingId,
        public readonly int $wagonId,
        public readonly int $sequence,
        public readonly float $loadriteWeightMt,
        public readonly string $weightSource,
        public readonly float $percentage,
        public readonly string $status,
    ) {}

    public function broadcastOn(): array
    {
        return [new PrivateChannel('siding.'.$this->sidingId)];
    }

    public function broadcastAs(): string
    {
        return 'wagon.weight.updated';
    }

    public function broadcastWith(): array
    {
        return [
            'wagon_id' => $this->wagonId,
            'sequence' => $this->sequence,
            'loadrite_weight_mt' => $this->loadriteWeightMt,
            'weight_source' => $this->weightSource,
            'percentage' => $this->percentage,
            'status' => $this->status,
        ];
    }
}
```

- [ ] **Step 2: Create `RakeStatusUpdated`**

Create `app/Events/RakeStatusUpdated.php`:

```php
<?php

declare(strict_types=1);

namespace App\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

final class RakeStatusUpdated implements ShouldBroadcast
{
    use Dispatchable;
    use InteractsWithSockets;
    use SerializesModels;

    public function __construct(
        public readonly int $sidingId,
        public readonly int $rakeId,
        public readonly string $status,
        public readonly int $wagonsLoaded,
        public readonly int $wagonCount,
        public readonly ?string $placementTime,
        public readonly ?string $loadingEndTime,
    ) {}

    public function broadcastOn(): array
    {
        return [new PrivateChannel('siding.'.$this->sidingId)];
    }

    public function broadcastAs(): string
    {
        return 'rake.status.updated';
    }

    public function broadcastWith(): array
    {
        return [
            'rake_id' => $this->rakeId,
            'status' => $this->status,
            'wagons_loaded' => $this->wagonsLoaded,
            'wagon_count' => $this->wagonCount,
            'placement_time' => $this->placementTime,
            'loading_end_time' => $this->loadingEndTime,
        ];
    }
}
```

- [ ] **Step 3: Add channel authorization to `routes/channels.php`**

Add at the bottom of `routes/channels.php`:

```php
Broadcast::channel('siding.{sidingId}', function ($user, int $sidingId): bool {
    $siding = \App\Models\Siding::query()->find($sidingId);

    return $siding && $user->can('view', $siding);
});
```

- [ ] **Step 4: Commit**

```bash
git add app/Events/WagonWeightUpdated.php app/Events/RakeStatusUpdated.php routes/channels.php
git commit -m "feat: add WagonWeightUpdated and RakeStatusUpdated broadcast events, add siding channel auth"
```

---

### Task 3: Dispatch `WagonWeightUpdated` from `SyncLoadriteWeightJob`

**Files:**
- Modify: `app/Jobs/SyncLoadriteWeightJob.php`

- [ ] **Step 1: Add dispatch after weight update**

In `app/Jobs/SyncLoadriteWeightJob.php`, after `$wagonLoading->update($updates);`, add:

```php
\App\Events\WagonWeightUpdated::dispatch(
    sidingId: $this->sidingId,
    wagonId: $wagonLoading->wagon_id,
    sequence: $this->event['Sequence'],
    loadriteWeightMt: (float) $this->event['Weight'],
    weightSource: $wagonLoading->fresh()->weight_source,
    percentage: $wagonLoading->cc_capacity_mt > 0
        ? round(($this->event['Weight'] / (float) $wagonLoading->cc_capacity_mt) * 100, 1)
        : 0.0,
    status: $wagonLoading->fresh()->weight_source === 'weighbridge' ? 'loaded' : 'loading',
);
```

- [ ] **Step 2: Run existing SyncLoadriteWeightJob tests to verify no regressions**

```bash
php artisan test --compact --filter=SyncLoadriteWeightJobTest
```

Expected: all PASS.

- [ ] **Step 3: Commit**

```bash
git add app/Jobs/SyncLoadriteWeightJob.php
git commit -m "feat: dispatch WagonWeightUpdated broadcast from SyncLoadriteWeightJob"
```

---

### Task 4: Controller and Inertia page route for siding monitor

**Files:**
- Create: `app/Http/Controllers/Sidings/SidingMonitorController.php`
- Modify: `routes/web.php`

- [ ] **Step 1: Create the controller**

```bash
php artisan make:controller Sidings/SidingMonitorController --no-interaction
```

Replace `app/Http/Controllers/Sidings/SidingMonitorController.php`:

```php
<?php

declare(strict_types=1);

namespace App\Http\Controllers\Sidings;

use App\Http\Controllers\Controller;
use App\Models\Rake;
use App\Models\Siding;
use Inertia\Inertia;
use Inertia\Response;

final class SidingMonitorController extends Controller
{
    public function show(Siding $siding): Response
    {
        $this->authorize('view', $siding);

        $activeRake = Rake::query()
            ->where('siding_id', $siding->id)
            ->whereIn('status', ['loading', 'placed'])
            ->with([
                'wagons',
                'wagonLoadings' => fn ($q) => $q->with('wagon'),
                'siding.sectionTimers',
            ])
            ->latest('placement_time')
            ->first();

        $freeMinutes = $siding->sectionTimers()
            ->where('section_name', 'loading')
            ->value('free_minutes') ?? 300;

        return Inertia::render('sidings/monitor', [
            'siding' => $siding->only('id', 'name'),
            'rake' => $activeRake ? [
                'id' => $activeRake->id,
                'status' => $activeRake->status,
                'wagon_count' => $activeRake->wagon_count,
                'placement_time' => $activeRake->placement_time?->toIso8601String(),
                'loading_end_time' => $activeRake->loading_end_time?->toIso8601String(),
                'wagons_loaded' => $activeRake->wagonLoadings->where('weight_source', 'weighbridge')->count(),
            ] : null,
            'wagons' => $activeRake
                ? $activeRake->wagonLoadings->map(fn ($wl) => [
                    'id' => $wl->wagon_id,
                    'sequence' => $wl->wagon?->wagon_number,
                    'loadrite_weight_mt' => $wl->loadrite_weight_mt,
                    'loaded_quantity_mt' => $wl->loaded_quantity_mt,
                    'cc_capacity_mt' => $wl->cc_capacity_mt,
                    'weight_source' => $wl->weight_source,
                    'percentage' => $wl->cc_capacity_mt > 0
                        ? round((float) (($wl->loadrite_weight_mt ?? $wl->loaded_quantity_mt ?? 0) / $wl->cc_capacity_mt) * 100, 1)
                        : 0,
                    'loadrite_override' => $wl->loadrite_override,
                ])->values()
                : [],
            'free_minutes' => $freeMinutes,
            'loadrite_active' => true,
        ]);
    }
}
```

- [ ] **Step 2: Add route to `routes/web.php`**

Find the existing siding routes block and add:

```php
Route::get('sidings/{siding}/monitor', [\App\Http\Controllers\Sidings\SidingMonitorController::class, 'show'])
    ->name('sidings.monitor');
```

- [ ] **Step 3: Run pint**

```bash
vendor/bin/pint app/Http/Controllers/Sidings/SidingMonitorController.php --format agent
```

- [ ] **Step 4: Commit**

```bash
git add app/Http/Controllers/Sidings/SidingMonitorController.php routes/web.php
git commit -m "feat: add SidingMonitorController and sidings.monitor route"
```

---

### Task 5: Zustand store — `useSidingStore`

**Files:**
- Create: `resources/js/stores/useSidingStore.ts`

- [ ] **Step 1: Create the store**

Create `resources/js/stores/useSidingStore.ts`:

```ts
import { createStore } from 'zustand';

export interface WagonSlice {
    id: number;
    sequence: number;
    loadriteWeightMt: number | null;
    loadedQuantityMt: number | null;
    ccCapacityMt: number;
    weightSource: 'manual' | 'loadrite' | 'weighbridge';
    percentage: number;
    loadriteOverride: boolean;
}

export interface RakeSlice {
    id: number;
    status: string;
    wagonCount: number;
    wagonsLoaded: number;
    placementTime: string | null;
    loadingEndTime: string | null;
}

export interface AlertItem {
    id: string;
    level: 'warning' | 'critical';
    wagonNumber: string;
    weightMt: number;
    ccMt: number;
    percentage: number;
    receivedAt: string;
}

interface SidingState {
    wagons: Record<number, WagonSlice>;
    rake: RakeSlice | null;
    alerts: AlertItem[];
    loadriteActive: boolean;
}

interface SidingActions {
    init: (props: { wagons: WagonSlice[]; rake: RakeSlice | null; loadriteActive: boolean }) => void;
    updateWagon: (event: {
        wagon_id: number;
        sequence: number;
        loadrite_weight_mt: number;
        weight_source: 'manual' | 'loadrite' | 'weighbridge';
        percentage: number;
        status: string;
    }) => void;
    updateRake: (event: {
        rake_id: number;
        status: string;
        wagons_loaded: number;
        wagon_count: number;
        placement_time: string | null;
        loading_end_time: string | null;
    }) => void;
    addAlert: (event: {
        wagon_id: number;
        wagon_number: string;
        weight_mt: number;
        cc_mt: number;
        percentage: number;
        level: 'warning' | 'critical';
    }) => void;
}

export type SidingStore = SidingState & SidingActions;

export function createSidingStore() {
    return createStore<SidingStore>()((set) => ({
        wagons: {},
        rake: null,
        alerts: [],
        loadriteActive: false,

        init: ({ wagons, rake, loadriteActive }) => {
            const wagonsMap: Record<number, WagonSlice> = {};
            wagons.forEach((w) => { wagonsMap[w.sequence] = w; });
            set({ wagons: wagonsMap, rake, loadriteActive });
        },

        updateWagon: (event) => {
            set((state) => ({
                wagons: {
                    ...state.wagons,
                    [event.sequence]: {
                        ...state.wagons[event.sequence],
                        id: event.wagon_id,
                        sequence: event.sequence,
                        loadriteWeightMt: event.loadrite_weight_mt,
                        weightSource: event.weight_source,
                        percentage: event.percentage,
                    },
                },
            }));
        },

        updateRake: (event) => {
            set((state) => ({
                rake: state.rake
                    ? {
                        ...state.rake,
                        status: event.status,
                        wagonsLoaded: event.wagons_loaded,
                        wagonCount: event.wagon_count,
                        placementTime: event.placement_time,
                        loadingEndTime: event.loading_end_time,
                      }
                    : null,
            }));
        },

        addAlert: (event) => {
            const alert: AlertItem = {
                id: `${event.wagon_id}-${event.level}-${Date.now()}`,
                level: event.level,
                wagonNumber: event.wagon_number,
                weightMt: event.weight_mt,
                ccMt: event.cc_mt,
                percentage: event.percentage,
                receivedAt: new Date().toISOString(),
            };
            set((state) => ({
                alerts: [alert, ...state.alerts].slice(0, 10),
            }));
        },
    }));
}

export type SidingStoreApi = ReturnType<typeof createSidingStore>;
```

- [ ] **Step 2: Commit**

```bash
git add resources/js/stores/useSidingStore.ts
git commit -m "feat: add useSidingStore Zustand store for real-time siding data"
```

---

### Task 6: `WagonBlock` and `WagonTooltip` components

**Files:**
- Create: `resources/js/components/SidingMonitor/WagonBlock.tsx`
- Create: `resources/js/components/SidingMonitor/WagonTooltip.tsx`

- [ ] **Step 1: Create directory**

```bash
mkdir -p resources/js/components/SidingMonitor
```

- [ ] **Step 2: Create `WagonTooltip`**

Create `resources/js/components/SidingMonitor/WagonTooltip.tsx`:

```tsx
import { motion, AnimatePresence } from 'framer-motion';

interface Props {
    wagon: {
        sequence: number;
        loadriteWeightMt: number | null;
        loadedQuantityMt: number | null;
        ccCapacityMt: number;
        weightSource: string;
        percentage: number;
    };
    visible: boolean;
}

export function WagonTooltip({ wagon, visible }: Props) {
    const weight = wagon.loadriteWeightMt ?? wagon.loadedQuantityMt ?? 0;
    const sourceLabel = { manual: 'Manual', loadrite: 'Loadrite', weighbridge: 'Weighbridge' }[wagon.weightSource] ?? wagon.weightSource;

    return (
        <AnimatePresence>
            {visible && (
                <motion.div
                    initial={{ opacity: 0, scale: 0.95, y: 4 }}
                    animate={{ opacity: 1, scale: 1, y: 0 }}
                    exit={{ opacity: 0, scale: 0.95, y: 4 }}
                    transition={{ duration: 0.15, ease: 'easeOut' }}
                    className="absolute bottom-full left-1/2 mb-2 -translate-x-1/2 z-50 w-44 rounded-lg border border-slate-700 bg-slate-800 p-3 shadow-xl"
                    role="tooltip"
                >
                    <p className="mb-1 font-mono text-xs font-semibold text-slate-200">
                        Wagon #{wagon.sequence}
                    </p>
                    <div className="space-y-0.5 text-xs text-slate-400">
                        <div className="flex justify-between">
                            <span>Weight</span>
                            <span className="font-mono text-slate-200">{weight.toFixed(1)} MT</span>
                        </div>
                        <div className="flex justify-between">
                            <span>CC</span>
                            <span className="font-mono text-slate-200">{wagon.ccCapacityMt.toFixed(1)} MT</span>
                        </div>
                        <div className="flex justify-between">
                            <span>Loading</span>
                            <span className={`font-mono font-semibold ${wagon.percentage >= 100 ? 'text-red-400' : wagon.percentage >= 90 ? 'text-amber-400' : 'text-green-400'}`}>
                                {wagon.percentage.toFixed(1)}%
                            </span>
                        </div>
                        <div className="flex justify-between">
                            <span>Source</span>
                            <span className="rounded bg-slate-700 px-1 text-slate-300">{sourceLabel}</span>
                        </div>
                    </div>
                    <div className="absolute left-1/2 top-full -translate-x-1/2 border-4 border-transparent border-t-slate-700" />
                </motion.div>
            )}
        </AnimatePresence>
    );
}
```

- [ ] **Step 3: Create `WagonBlock`**

Create `resources/js/components/SidingMonitor/WagonBlock.tsx`:

```tsx
import { motion, useReducedMotion } from 'framer-motion';
import { useState } from 'react';
import { WagonTooltip } from './WagonTooltip';
import type { WagonSlice } from '@/stores/useSidingStore';

interface Props {
    wagon: WagonSlice;
}

function getBlockStyle(wagon: WagonSlice): { bg: string; border: string } {
    if (wagon.weightSource === 'weighbridge') return { bg: 'bg-blue-950', border: 'border-blue-600' };
    if (wagon.percentage >= 100) return { bg: 'bg-red-950', border: 'border-red-500' };
    if (wagon.percentage >= 90) return { bg: 'bg-amber-950', border: 'border-amber-500' };
    if (wagon.weightSource === 'loadrite') return { bg: 'bg-green-900', border: 'border-green-500' };
    if (wagon.weightSource === 'manual' && wagon.loadedQuantityMt !== null) return { bg: 'bg-green-950', border: 'border-green-700' };
    return { bg: 'bg-slate-800', border: 'border-slate-600' };
}

function getAnimateVariant(wagon: WagonSlice) {
    if (wagon.percentage >= 100) return 'critical';
    if (wagon.percentage >= 90) return 'warning';
    return 'idle';
}

const variants = {
    idle: { scale: 1, x: 0 },
    warning: { scale: [1, 1.08, 1] as number[] },
    critical: { x: [-4, 4, -4, 4, 0] as number[] },
};

export function WagonBlock({ wagon }: Props) {
    const [tooltipVisible, setTooltipVisible] = useState(false);
    const shouldReduceMotion = useReducedMotion();
    const { bg, border } = getBlockStyle(wagon);
    const animateKey = getAnimateVariant(wagon);

    return (
        <div className="relative" role="button" tabIndex={0}
            onMouseEnter={() => setTooltipVisible(true)}
            onMouseLeave={() => setTooltipVisible(false)}
            onClick={() => setTooltipVisible((v) => !v)}
            onKeyDown={(e) => e.key === 'Enter' && setTooltipVisible((v) => !v)}
        >
            <motion.div
                key={`${wagon.sequence}-${wagon.loadriteWeightMt}`}
                animate={shouldReduceMotion ? 'idle' : animateKey}
                variants={variants}
                transition={{ duration: 0.25, ease: 'easeOut' }}
                className={`
                    h-7 w-7 rounded border cursor-pointer
                    sm:h-10 sm:w-10
                    lg:h-12 lg:w-12
                    ${bg} ${border}
                    flex items-center justify-center
                    transition-colors duration-200
                `}
            >
                <span className="hidden text-[10px] font-mono text-slate-400 sm:block lg:text-xs">
                    {wagon.sequence}
                </span>
            </motion.div>
            <WagonTooltip wagon={wagon} visible={tooltipVisible} />
        </div>
    );
}
```

- [ ] **Step 4: Commit**

```bash
git add resources/js/components/SidingMonitor/WagonBlock.tsx resources/js/components/SidingMonitor/WagonTooltip.tsx
git commit -m "feat: add WagonBlock and WagonTooltip components with Framer Motion animations"
```

---

### Task 7: `WagonTrain` component

**Files:**
- Create: `resources/js/components/SidingMonitor/WagonTrain.tsx`

- [ ] **Step 1: Create `WagonTrain`**

Create `resources/js/components/SidingMonitor/WagonTrain.tsx`:

```tsx
import { motion } from 'framer-motion';
import { WagonBlock } from './WagonBlock';
import type { WagonSlice } from '@/stores/useSidingStore';

interface Props {
    wagons: Record<number, WagonSlice>;
}

export function WagonTrain({ wagons }: Props) {
    const sortedWagons = Object.values(wagons).sort((a, b) => a.sequence - b.sequence);

    return (
        <div className="w-full overflow-x-auto pb-2">
            {/* Tablet+: horizontal row */}
            <div className="hidden sm:flex flex-nowrap gap-1.5 min-w-max">
                {sortedWagons.map((wagon, i) => (
                    <motion.div
                        key={wagon.sequence}
                        initial={{ opacity: 0, x: -8 }}
                        animate={{ opacity: 1, x: 0 }}
                        transition={{ delay: i * 0.03, duration: 0.2, ease: 'easeOut' }}
                    >
                        <WagonBlock wagon={wagon} />
                    </motion.div>
                ))}
            </div>
            {/* Mobile: wrap grid, 8 per row */}
            <div className="grid grid-cols-8 gap-1 sm:hidden">
                {sortedWagons.map((wagon) => (
                    <WagonBlock key={wagon.sequence} wagon={wagon} />
                ))}
            </div>
        </div>
    );
}
```

- [ ] **Step 2: Commit**

```bash
git add resources/js/components/SidingMonitor/WagonTrain.tsx
git commit -m "feat: add WagonTrain component — horizontal row tablet+, grid mobile"
```

---

### Task 8: `LoadingRing` component

**Files:**
- Create: `resources/js/components/SidingMonitor/LoadingRing.tsx`

- [ ] **Step 1: Create `LoadingRing`**

Create `resources/js/components/SidingMonitor/LoadingRing.tsx`:

```tsx
import { motion, useReducedMotion } from 'framer-motion';

interface Props {
    percentage: number;
    weightMt: number;
    ccMt: number;
    sequence: number;
}

export function LoadingRing({ percentage, weightMt, ccMt, sequence }: Props) {
    const shouldReduceMotion = useReducedMotion();
    const radius = 54;
    const circumference = 2 * Math.PI * radius;
    const clampedPct = Math.min(percentage, 100);
    const offset = circumference - (clampedPct / 100) * circumference;

    const ringColor = percentage >= 100
        ? '#ef4444'
        : percentage >= 90
        ? '#f59e0b'
        : '#22c55e';

    return (
        <div className="flex flex-col items-center gap-2">
            <div className="relative h-36 w-36">
                <svg className="h-full w-full -rotate-90" viewBox="0 0 120 120">
                    <circle cx="60" cy="60" r={radius} fill="none" stroke="#1e293b" strokeWidth="8" />
                    <motion.circle
                        cx="60" cy="60" r={radius}
                        fill="none"
                        stroke={ringColor}
                        strokeWidth="8"
                        strokeLinecap="round"
                        strokeDasharray={circumference}
                        animate={{ strokeDashoffset: shouldReduceMotion ? offset : offset }}
                        initial={{ strokeDashoffset: circumference }}
                        transition={{ duration: 0.3, ease: 'easeOut' }}
                        style={{ strokeDashoffset: offset }}
                    />
                </svg>
                <div className="absolute inset-0 flex flex-col items-center justify-center">
                    <span className={`font-mono text-2xl font-bold ${percentage >= 100 ? 'text-red-400' : percentage >= 90 ? 'text-amber-400' : 'text-green-400'}`}>
                        {percentage.toFixed(0)}%
                    </span>
                    <span className="font-mono text-xs text-slate-400">{weightMt.toFixed(1)} MT</span>
                </div>
            </div>
            <div className="text-center">
                <p className="text-xs text-slate-500">Wagon #{sequence}</p>
                <p className="font-mono text-xs text-slate-400">CC: {ccMt.toFixed(1)} MT</p>
            </div>
        </div>
    );
}
```

- [ ] **Step 2: Commit**

```bash
git add resources/js/components/SidingMonitor/LoadingRing.tsx
git commit -m "feat: add LoadingRing SVG animated component"
```

---

### Task 9: `CountdownTimer` component

**Files:**
- Create: `resources/js/components/SidingMonitor/CountdownTimer.tsx`

- [ ] **Step 1: Create `CountdownTimer`**

Create `resources/js/components/SidingMonitor/CountdownTimer.tsx`:

```tsx
import { motion, useReducedMotion } from 'framer-motion';
import { useEffect, useRef, useState } from 'react';

interface Props {
    placementTime: string | null;
    freeMinutes: number;
}

function formatHMS(totalSeconds: number): string {
    const abs = Math.abs(totalSeconds);
    const h = Math.floor(abs / 3600).toString().padStart(2, '0');
    const m = Math.floor((abs % 3600) / 60).toString().padStart(2, '0');
    const s = Math.floor(abs % 60).toString().padStart(2, '0');
    return `${h}:${m}:${s}`;
}

export function CountdownTimer({ placementTime, freeMinutes }: Props) {
    const [elapsed, setElapsed] = useState(0);
    const rafRef = useRef<number>(0);
    const shouldReduceMotion = useReducedMotion();

    useEffect(() => {
        if (!placementTime) return;

        const placed = new Date(placementTime).getTime();

        const tick = () => {
            const elapsedSeconds = Math.floor((Date.now() - placed) / 1000);
            setElapsed(elapsedSeconds);
            rafRef.current = requestAnimationFrame(tick);
        };

        rafRef.current = requestAnimationFrame(tick);
        return () => cancelAnimationFrame(rafRef.current);
    }, [placementTime]);

    const freeSeconds = freeMinutes * 60;
    const remainingSeconds = freeSeconds - elapsed;
    const isOverrun = remainingSeconds < 0;

    return (
        <div className="flex items-center gap-6 rounded-xl border border-slate-800 bg-slate-900/60 px-6 py-4">
            <Stat label="Elapsed" value={formatHMS(elapsed)} mono />
            <div className="h-8 w-px bg-slate-700" />
            <motion.div
                animate={shouldReduceMotion ? {} : { scale: isOverrun ? [1, 1.05, 1] : 1 }}
                transition={{ duration: 0.3, ease: 'easeOut' }}
            >
                <Stat
                    label={isOverrun ? 'Overrun' : 'Remaining'}
                    value={(isOverrun ? '+' : '') + formatHMS(Math.abs(remainingSeconds))}
                    mono
                    highlight={isOverrun ? 'red' : 'green'}
                />
            </motion.div>
            <div className="h-8 w-px bg-slate-700" />
            <Stat label="Free window" value={formatHMS(freeSeconds)} mono />
        </div>
    );
}

function Stat({ label, value, mono, highlight }: { label: string; value: string; mono?: boolean; highlight?: 'red' | 'green' }) {
    const color = highlight === 'red' ? 'text-red-400' : highlight === 'green' ? 'text-green-400' : 'text-slate-200';
    return (
        <div className="text-center">
            <p className="mb-0.5 text-xs text-slate-500">{label}</p>
            <p className={`${mono ? 'font-mono' : ''} text-lg font-semibold ${color}`}>{value}</p>
        </div>
    );
}
```

- [ ] **Step 2: Commit**

```bash
git add resources/js/components/SidingMonitor/CountdownTimer.tsx
git commit -m "feat: add CountdownTimer with requestAnimationFrame tick and overrun state"
```

---

### Task 10: `AlertsFeed` and `StatsBar` components

**Files:**
- Create: `resources/js/components/SidingMonitor/AlertsFeed.tsx`
- Create: `resources/js/components/SidingMonitor/StatsBar.tsx`

- [ ] **Step 1: Create `AlertsFeed`**

Create `resources/js/components/SidingMonitor/AlertsFeed.tsx`:

```tsx
import { AnimatePresence, motion } from 'framer-motion';
import type { AlertItem } from '@/stores/useSidingStore';

interface Props {
    alerts: AlertItem[];
}

export function AlertsFeed({ alerts }: Props) {
    if (alerts.length === 0) {
        return (
            <div className="rounded-xl border border-slate-800 bg-slate-900/40 p-4 text-center text-sm text-slate-600">
                No alerts
            </div>
        );
    }

    return (
        <div className="space-y-2">
            <AnimatePresence initial={false}>
                {alerts.map((alert) => (
                    <motion.div
                        key={alert.id}
                        initial={{ opacity: 0, x: 60 }}
                        animate={{ opacity: 1, x: 0 }}
                        exit={{ opacity: 0, x: 60, height: 0, marginBottom: 0 }}
                        transition={{ duration: 0.2, ease: 'easeOut' }}
                        className={`flex items-center justify-between rounded-lg border px-4 py-2.5 text-sm ${
                            alert.level === 'critical'
                                ? 'border-red-800 bg-red-950/60 text-red-300'
                                : 'border-amber-800 bg-amber-950/60 text-amber-300'
                        }`}
                    >
                        <div className="flex items-center gap-2">
                            <span className="font-semibold">
                                {alert.level === 'critical' ? '🔴' : '⚠️'} Wagon {alert.wagonNumber}
                            </span>
                            <span className="text-xs opacity-75">
                                {alert.percentage.toFixed(1)}% CC — {alert.weightMt.toFixed(1)} MT
                            </span>
                        </div>
                        <span className="font-mono text-xs opacity-50">
                            {new Date(alert.receivedAt).toLocaleTimeString()}
                        </span>
                    </motion.div>
                ))}
            </AnimatePresence>
        </div>
    );
}
```

- [ ] **Step 2: Create `StatsBar`**

Create `resources/js/components/SidingMonitor/StatsBar.tsx`:

```tsx
interface Props {
    sidingName: string;
    wagonsLoaded: number;
    wagonCount: number;
    loadriteActive: boolean;
}

export function StatsBar({ sidingName, wagonsLoaded, wagonCount, loadriteActive }: Props) {
    const pct = wagonCount > 0 ? Math.round((wagonsLoaded / wagonCount) * 100) : 0;

    return (
        <div className="flex items-center justify-between rounded-xl border border-slate-800 bg-slate-900/60 px-6 py-3">
            <div className="flex items-center gap-3">
                <div className="h-2.5 w-2.5 animate-pulse rounded-full bg-green-500" />
                <h1 className="text-lg font-semibold text-slate-100">{sidingName}</h1>
                <span className="rounded-full bg-slate-800 px-2.5 py-0.5 font-mono text-xs text-slate-400">
                    {wagonsLoaded} / {wagonCount} wagons · {pct}%
                </span>
            </div>
            <div className={`rounded-full px-3 py-1 text-xs font-medium ${loadriteActive ? 'bg-green-900/50 text-green-400 border border-green-800' : 'bg-slate-800 text-slate-500'}`}>
                Loadrite: {loadriteActive ? 'Active' : 'Inactive'}
            </div>
        </div>
    );
}
```

- [ ] **Step 3: Commit**

```bash
git add resources/js/components/SidingMonitor/AlertsFeed.tsx resources/js/components/SidingMonitor/StatsBar.tsx
git commit -m "feat: add AlertsFeed (AnimatePresence) and StatsBar components"
```

---

### Task 11: Inertia page — `sidings/monitor.tsx`

**Files:**
- Create: `resources/js/pages/sidings/monitor.tsx`

- [ ] **Step 1: Create sidings directory if needed**

```bash
mkdir -p resources/js/pages/sidings
```

- [ ] **Step 2: Create the page**

Create `resources/js/pages/sidings/monitor.tsx`:

```tsx
import { createContext, useContext, useEffect, useRef, StoreApi } from 'react';
import { useStore } from 'zustand';
import { createSidingStore, SidingStore } from '@/stores/useSidingStore';
import { StatsBar } from '@/components/SidingMonitor/StatsBar';
import { CountdownTimer } from '@/components/SidingMonitor/CountdownTimer';
import { WagonTrain } from '@/components/SidingMonitor/WagonTrain';
import { LoadingRing } from '@/components/SidingMonitor/LoadingRing';
import { AlertsFeed } from '@/components/SidingMonitor/AlertsFeed';

const StoreContext = createContext<StoreApi<SidingStore> | null>(null);

function useSidingStore<T>(selector: (state: SidingStore) => T): T {
    const store = useContext(StoreContext);
    if (!store) throw new Error('Missing SidingStoreProvider');
    return useStore(store, selector);
}

interface PageProps {
    siding: { id: number; name: string };
    rake: {
        id: number;
        status: string;
        wagon_count: number;
        wagons_loaded: number;
        placement_time: string | null;
        loading_end_time: string | null;
    } | null;
    wagons: Array<{
        id: number;
        sequence: number;
        loadrite_weight_mt: number | null;
        loaded_quantity_mt: number | null;
        cc_capacity_mt: number;
        weight_source: 'manual' | 'loadrite' | 'weighbridge';
        percentage: number;
        loadrite_override: boolean;
    }>;
    free_minutes: number;
    loadrite_active: boolean;
}

function SidingMonitorContent({ siding, free_minutes, loadrite_active }: PageProps) {
    const wagons = useSidingStore((s) => s.wagons);
    const rake = useSidingStore((s) => s.rake);
    const alerts = useSidingStore((s) => s.alerts);

    const activeWagon = Object.values(wagons)
        .filter((w) => w.weightSource === 'loadrite')
        .sort((a, b) => b.sequence - a.sequence)[0] ?? null;

    return (
        <div className="min-h-screen bg-[#020617] p-4 text-slate-100 lg:p-6">
            <div className="mx-auto max-w-7xl space-y-4">
                <StatsBar
                    sidingName={siding.name}
                    wagonsLoaded={rake?.wagonsLoaded ?? 0}
                    wagonCount={rake?.wagonCount ?? 0}
                    loadriteActive={loadrite_active}
                />

                <CountdownTimer
                    placementTime={rake?.placementTime ?? null}
                    freeMinutes={free_minutes}
                />

                <div className="grid gap-4 lg:grid-cols-[1fr_200px]">
                    <div className="rounded-xl border border-slate-800 bg-slate-900/40 p-4">
                        <h2 className="mb-3 text-sm font-medium text-slate-500">Wagon Train</h2>
                        <WagonTrain wagons={wagons} />
                    </div>

                    {activeWagon && (
                        <div className="flex items-center justify-center rounded-xl border border-slate-800 bg-slate-900/40 p-4">
                            <LoadingRing
                                percentage={activeWagon.percentage}
                                weightMt={activeWagon.loadriteWeightMt ?? 0}
                                ccMt={activeWagon.ccCapacityMt}
                                sequence={activeWagon.sequence}
                            />
                        </div>
                    )}
                </div>

                <div className="rounded-xl border border-slate-800 bg-slate-900/40 p-4">
                    <h2 className="mb-3 text-sm font-medium text-slate-500">Alerts</h2>
                    <AlertsFeed alerts={alerts} />
                </div>
            </div>
        </div>
    );
}

export default function Monitor(props: PageProps) {
    const storeRef = useRef<ReturnType<typeof createSidingStore>>(null!);
    if (!storeRef.current) {
        storeRef.current = createSidingStore();
    }

    const { siding, wagons, rake, loadrite_active } = props;

    useEffect(() => {
        storeRef.current.getState().init({
            wagons: wagons.map((w) => ({
                id: w.id,
                sequence: w.sequence,
                loadriteWeightMt: w.loadrite_weight_mt,
                loadedQuantityMt: w.loaded_quantity_mt,
                ccCapacityMt: w.cc_capacity_mt,
                weightSource: w.weight_source,
                percentage: w.percentage,
                loadriteOverride: w.loadrite_override,
            })),
            rake: rake
                ? {
                    id: rake.id,
                    status: rake.status,
                    wagonCount: rake.wagon_count,
                    wagonsLoaded: rake.wagons_loaded,
                    placementTime: rake.placement_time,
                    loadingEndTime: rake.loading_end_time,
                  }
                : null,
            loadriteActive: load_active,
        });

        const channel = (window as any).Echo?.private(`siding.${siding.id}`);

        if (channel) {
            channel
                .listen('.wagon.weight.updated', (e: any) => storeRef.current.getState().updateWagon(e))
                .listen('.wagon.overload.warning', (e: any) => storeRef.current.getState().addAlert(e))
                .listen('.wagon.overload.critical', (e: any) => storeRef.current.getState().addAlert(e))
                .listen('.rake.status.updated', (e: any) => storeRef.current.getState().updateRake(e));
        }

        return () => {
            (window as any).Echo?.leave(`siding.${siding.id}`);
        };
    }, [siding.id]);

    return (
        <StoreContext.Provider value={storeRef.current}>
            <SidingMonitorContent {...props} />
        </StoreContext.Provider>
    );
}
```

- [ ] **Step 2: Fix typo in useEffect (`load_active` → `loadrite_active`)**

In the `init` call inside `useEffect`, change `load_active` to `loadrite_active`:

```ts
loadriteActive: loadrite_active,
```

- [ ] **Step 3: Build to verify no TypeScript errors**

```bash
npm run build 2>&1 | tail -20
```

Expected: build succeeds with no errors.

- [ ] **Step 4: Commit**

```bash
git add resources/js/pages/sidings/monitor.tsx
git commit -m "feat: add sidings/monitor Inertia page wired to Zustand store and Reverb"
```

---

### Task 12: Backend tests for broadcast events and channel auth

**Files:**
- Create: `tests/Feature/SidingMonitorBroadcastTest.php`

- [ ] **Step 1: Write tests**

```bash
php artisan make:test --pest SidingMonitorBroadcastTest --no-interaction
```

Replace `tests/Feature/SidingMonitorBroadcastTest.php`:

```php
<?php

declare(strict_types=1);

use App\Events\RakeStatusUpdated;
use App\Events\WagonWeightUpdated;
use App\Jobs\SyncLoadriteWeightJob;
use App\Models\Rake;
use App\Models\Siding;
use App\Models\User;
use App\Models\Wagon;
use App\Models\WagonLoading;
use Illuminate\Support\Facades\Event;

it('WagonWeightUpdated is dispatched after SyncLoadriteWeightJob updates a wagon', function (): void {
    Event::fake([WagonWeightUpdated::class]);

    $rake = Rake::factory()->create(['siding_id' => 1, 'status' => 'loading']);
    $wagon = Wagon::factory()->create(['wagon_number' => 1]);
    WagonLoading::factory()->create([
        'rake_id' => $rake->id,
        'wagon_id' => $wagon->id,
        'cc_capacity_mt' => 68,
        'weight_source' => 'manual',
        'loadrite_override' => false,
    ]);

    (new SyncLoadriteWeightJob(['Sequence' => 1, 'Weight' => 50.0, 'Timestamp' => now()->toIso8601String()], 1))->handle();

    Event::assertDispatched(WagonWeightUpdated::class, fn ($e) => $e->sidingId === 1 && $e->loadriteWeightMt === 50.0);
});

it('authenticated user with view permission can subscribe to siding channel', function (): void {
    $siding = Siding::factory()->create();
    $user = User::factory()->create();
    $user->givePermissionTo('view sidings');

    $this->actingAs($user)
        ->post('/broadcasting/auth', ['channel_name' => "private-siding.{$siding->id}"])
        ->assertStatus(200);
});

it('user without permission cannot subscribe to siding channel', function (): void {
    $siding = Siding::factory()->create();
    $user = User::factory()->create();

    $this->actingAs($user)
        ->post('/broadcasting/auth', ['channel_name' => "private-siding.{$siding->id}"])
        ->assertStatus(403);
});
```

- [ ] **Step 2: Run tests**

```bash
php artisan test --compact --filter=SidingMonitorBroadcastTest
```

Expected: all PASS.

- [ ] **Step 3: Commit**

```bash
git add tests/Feature/SidingMonitorBroadcastTest.php
git commit -m "test: add broadcast event and channel auth tests for siding monitor"
```

---

### Task 13: Full test suite pass

- [ ] **Step 1: Run full test suite**

```bash
php artisan test --compact
```

Expected: all PASS.

- [ ] **Step 2: Build frontend**

```bash
npm run build 2>&1 | tail -10
```

Expected: no TypeScript or Vite errors.

- [ ] **Step 3: Run pint**

```bash
vendor/bin/pint --dirty --format agent
git add -A
git commit -m "style: pint formatting fixes for siding monitor"
```

- [ ] **Step 4: Smoke test in browser**

Get the siding monitor URL:

```bash
php artisan route:list --name=sidings.monitor
```

Navigate to `{app-url}/sidings/{id}/monitor` and verify:
- Dark OLED background renders
- Wagon train shows color-coded blocks
- CountdownTimer ticks every second
- No console errors
