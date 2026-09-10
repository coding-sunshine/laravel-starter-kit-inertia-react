# Work Stream 3: Real-Time Siding Monitor Dashboard

**Date:** 2026-04-30  
**Approach:** B — Zustand Store + Component Tree  
**Status:** Approved for implementation

---

## Overview

Build a real-time siding monitor dashboard that:
1. Shows a per-wagon animated train visualization (color-coded by loading status)
2. Displays a live countdown timer (elapsed / remaining / free window)
3. Feeds live weight updates from Loadrite via Reverb WebSockets
4. Shows overload alerts (warning 90%+, critical 100%+) with animated feed
5. Works on tablet (primary — ops use iPads at the siding) and desktop

**Scope:** Single siding detail view first. Central multi-siding monitoring view reuses the same components and store — built second.

---

## Design System

| Token | Value | Use |
|---|---|---|
| Background | `#020617` | Page background (OLED dark) |
| Surface | `#0F172A` | Cards, wagon blocks |
| Surface raised | `#1E293B` | Tooltips, popovers, modals |
| Accent green | `#22C55E` | Loaded wagons, positive states, CTA |
| Text primary | `#F8FAFC` | All primary text |
| Text muted | `#94A3B8` | Labels, secondary info |
| Animation micro | 150–200ms | Tooltips, hover states |
| Animation data | 250–300ms | Weight updates, ring fills |
| Easing enter | `ease-out` | Elements entering |
| Easing exit | `ease-in` | Elements leaving |

**Typography:** Inter (UI text) + JetBrains Mono (numbers — weights, timers, percentages)  
**Icons:** Lucide React (consistent set, SVG, no emojis)  
**Motion:** Framer Motion — `prefers-reduced-motion` respected via `useReducedMotion()`

---

## Architecture & Data Flow

```
Laravel (Inertia props)
  → initial rake + wagons + siding data on page load

Laravel Reverb (WebSocket)
  → WagonWeightUpdated       (from SyncLoadriteWeightJob)
  → WagonOverloadWarning     (from EvaluateOverloadAlertJob)
  → WagonOverloadCritical    (from EvaluateOverloadAlertJob)
  → RakeStatusUpdated        (on rake status change)

useSidingStore(sidingId)  [Zustand — keyed per siding]
  → wagons[]               { id, sequence, weight_mt, cc_mt, percentage, status, weight_source }
  → rake                   { id, placement_time, wagon_count, wagons_loaded, status }
  → alerts[]               last 10, newest first

Components subscribe to their own slice only:
  WagonBlock(seq)  →  wagons[seq] only  (no other re-renders)
  CountdownTimer   →  rake.placement_time + free_minutes only
  AlertsFeed       →  alerts[] only
  LoadingRing      →  active wagon percentage only
  StatsBar         →  rake.wagons_loaded + rake.wagon_count
```

**Reverb channel:** `private-siding.{siding_id}` — authenticated per Laravel channel authorization.

---

## Component Tree

```
pages/sidings/Show.tsx                    ← Inertia page, bootstraps store on mount
│
└── SidingMonitor/
    ├── StatsBar                          ← Wagons loaded / total, elapsed %, Loadrite status badge
    ├── CountdownTimer                    ← RAF tick, green→red when over free window
    ├── WagonTrain                        ← Horizontal scroll row (≥768px), wrap grid (mobile)
    │   └── WagonBlock × N               ← Per-wagon block, Framer spring on weight change
    │       └── WagonTooltip             ← Hover (desktop) / tap (mobile) popover
    ├── LoadingRing                       ← SVG animated ring, active wagon CC% fill
    └── AlertsFeed                        ← AnimatePresence list, newest-first, slide-in
```

**File locations:** `resources/js/components/SidingMonitor/`  
**Store location:** `resources/js/stores/useSidingStore.ts`

---

## Page Layout

```
┌─────────────────────────────────────────────────────┐
│  Dumka Siding          ● Live    [Loadrite: Active]  │  StatsBar
│  59 wagons  ·  42 loaded  ·  17 remaining            │
├─────────────────────────────────────────────────────┤
│  ⏱ 3h 24m elapsed   |  1h 36m remaining  |  5h free │  CountdownTimer
├──────────────────────────────────┬──────────────────┤
│                                  │                   │
│  ██ ██ ██ ██ ██ ██ ██ ██ ██ ██  │   Active wagon   │
│  ██ ██ ██ ██ ██ ██ ██ ██ ██ ██  │   ┌───────────┐  │
│  ██ ██ ██ ██ ██ ██ ██ ██ ██ ██  │   │  ◉  68%   │  │  LoadingRing
│  ── ── ── ── ──                  │   │  46.2 MT  │  │
│  WagonTrain                      │   │  CC: 68MT │  │
│                                  │   └───────────┘  │
├──────────────────────────────────┴──────────────────┤
│  ⚠ WAGON 31 — 91% CC — Loadrite        2 min ago    │  AlertsFeed
│  ✓ WAGON 28 — Loaded — 67.4 MT         8 min ago    │
└─────────────────────────────────────────────────────┘
```

---

## Responsive Behavior

| Breakpoint | WagonTrain | LoadingRing | Layout |
|---|---|---|---|
| Mobile `<768px` | Wrap grid, 8 per row, 28×28px blocks | Hidden | Single column |
| Tablet `≥768px` | Horizontal scroll row, 40×40px blocks | Sidebar right | Two column |
| Desktop `≥1280px` | Full row (no scroll needed), 48×48px | Sidebar right | Two column, wider |

Wagon block sequence number shown at 10px (tablet) / 12px (desktop). Hidden on mobile (blocks too small).

---

## Wagon Block Color States

| State | Background | Border | Framer Motion |
|---|---|---|---|
| Empty / waiting | `#1E293B` | `#334155` | — |
| Loading (Loadrite active) | `#78350f` amber-900 | `#f59e0b` | — |
| Loaded — manual | `#14532d` | `#4ade80` | — |
| Loaded — Loadrite | `#166534` | `#22c55e` | — |
| Loaded — weighbridge | `#1e3a5f` | `#3b82f6` | — |
| Warning 90%+ | `#78350f` | `#f59e0b` | Pulse border, 2× repeat |
| Critical 100%+ | `#7f1d1d` | `#ef4444` | Shake + pulse red |

---

## Framer Motion Animations

| Component | Trigger | Animation | Duration |
|---|---|---|---|
| `WagonBlock` | Weight update | `scale 1→1.08→1` + background color spring | 250ms spring |
| `WagonBlock` | Overload warning | Pulse amber border, 2× | 400ms |
| `WagonBlock` | Overload critical | `x: -4→4→-4→0` shake + red pulse | 300ms |
| `WagonTrain` | Page load | Wagons stagger-in left→right, 30ms delay each | 200ms ease-out |
| `AlertsFeed` item | New alert | `x: 60→0`, `opacity: 0→1` slide-in | 200ms ease-out |
| `AlertsFeed` item | Dismissed | `x: 0→60`, height collapse, `AnimatePresence` | 150ms ease-in |
| `CountdownTimer` | Crosses free window | Color spring green→red, `scale 1→1.05→1` | 300ms spring |
| `LoadingRing` | Percentage change | SVG `strokeDashoffset` spring | 300ms spring |
| `WagonTooltip` | Hover / tap | `scale: 0.95→1`, `opacity: 0→1` | 150ms spring |

All animations wrapped in `useReducedMotion()` check — reduced to instant transitions when user preference set.

---

## WagonTooltip Content

```
┌─────────────────────┐
│ Wagon #31  BOXN-456 │
│ ─────────────────── │
│ Weight:   62.4 MT   │
│ CC:       68.0 MT   │
│ Loading:  91.8%  ⚠  │
│ Source:  [Loadrite] │
└─────────────────────┘
```

- Desktop: triggered on hover (`onMouseEnter` / `onMouseLeave`), positioned above wagon block
- Mobile/tablet: triggered on tap (`onClick`), rendered as fixed bottom sheet with 44px+ touch targets
- Same component, different trigger + position per breakpoint (detect via `pointer: coarse` media query)

---

## Backend Events

### New broadcast events

**`WagonWeightUpdated`** — dispatched from `SyncLoadriteWeightJob` after updating `WagonLoading`:
```json
{
  "wagon_id": 123,
  "sequence": 31,
  "loadrite_weight_mt": 62.4,
  "weight_source": "loadrite",
  "percentage": 91.8,
  "status": "loading"
}
```

**`RakeStatusUpdated`** — dispatched on rake status transitions (loading_started, loading_completed):
```json
{
  "rake_id": 45,
  "status": "loading",
  "wagons_loaded": 42,
  "wagon_count": 59,
  "placement_time": "2026-04-30T08:00:00Z",
  "loading_end_time": null
}
```

`WagonOverloadWarning` and `WagonOverloadCritical` — already defined in Loadrite spec. No changes.

### Channel authorization

```php
// routes/channels.php
Broadcast::channel('siding.{sidingId}', function (User $user, int $sidingId) {
    return $user->can('view', Siding::find($sidingId));
});
```

### Frontend Reverb subscription (inside `useSidingStore`)

```ts
Echo.private(`siding.${sidingId}`)
  .listen('WagonWeightUpdated', (e) => store.updateWagon(e))
  .listen('WagonOverloadWarning', (e) => store.addAlert(e))
  .listen('WagonOverloadCritical', (e) => store.addAlert(e))
  .listen('RakeStatusUpdated', (e) => store.updateRake(e));

// Cleanup on unmount:
return () => Echo.leave(`siding.${sidingId}`);
```

---

## CountdownTimer Logic

- Server pushes `placement_time` (ISO8601) and `free_minutes` via Inertia props + `RakeStatusUpdated`
- Frontend computes `elapsed = now() - placement_time` locally via `requestAnimationFrame`
- `remaining = free_minutes - elapsed` — goes negative when overrunning (shown as overrun time in red)
- Reconciles on every `RakeStatusUpdated` Reverb event to re-anchor against server time
- Display: `HH:MM:SS` using JetBrains Mono

---

## Zustand Store Shape

```ts
interface SidingStore {
  wagons: Record<number, WagonSlice>   // keyed by sequence
  rake: RakeSlice
  alerts: AlertItem[]                  // max 10, newest first

  // Actions
  init: (props: InertiaPageProps) => void
  updateWagon: (event: WagonWeightUpdated) => void
  updateRake: (event: RakeStatusUpdated) => void
  addAlert: (event: OverloadEvent) => void
}
```

Store is created with `createStore` (not `create`) so multiple siding instances don't share state. Passed via React context to the component tree.

---

## Tests

### Backend (Pest)

**1. Unit — `WagonWeightUpdated` broadcast**
- `SyncLoadriteWeightJob` updates `WagonLoading` → assert `WagonWeightUpdated` dispatched
- Assert payload: `wagon_id`, `sequence`, `percentage`, `weight_source` correct

**2. Unit — `RakeStatusUpdated` broadcast**
- Rake status transitions → assert `RakeStatusUpdated` dispatched
- Assert `wagons_loaded` count correct

**3. Feature — Channel authorization**
- User with `view` permission on siding → can subscribe to `private-siding.{id}`
- User without permission → denied (403)

### Frontend (Vitest + React Testing Library)

**4. Unit — `WagonBlock`**
- Renders correct CSS class per status (empty / loading / loaded / warning / critical)
- Framer Motion `animate` variant set correctly per status (mock `framer-motion`)

**5. Unit — `CountdownTimer`**
- `placement_time` 3h ago, `free_minutes=300` → shows overrun in red
- `placement_time` 1h ago, `free_minutes=300` → shows remaining in green

**6. Unit — `useSidingStore`**
- `updateWagon()` → correct wagon slice updated, others unchanged
- `addAlert()` → prepends to `alerts[]`, caps at 10
- `WagonWeightUpdated` event → store updates correctly (mock `Echo`)

**7. Unit — `AlertsFeed`**
- New alert → `AnimatePresence` renders new item at top
- 11th alert → oldest removed from list

---

## Implementation Order

1. `WagonWeightUpdated` broadcast event class
2. `RakeStatusUpdated` broadcast event class
3. Add `WagonWeightUpdated` dispatch to `SyncLoadriteWeightJob`
4. Channel authorization in `routes/channels.php`
5. Zustand store — `useSidingStore.ts`
6. `WagonBlock` + `WagonTooltip` components
7. `WagonTrain` responsive container
8. `LoadingRing` SVG animated component
9. `CountdownTimer` RAF hook + component
10. `AlertsFeed` with `AnimatePresence`
11. `StatsBar` component
12. Inertia `Show.tsx` page — wire store + Reverb subscription
13. Pest tests (backend)
14. Vitest tests (frontend)
