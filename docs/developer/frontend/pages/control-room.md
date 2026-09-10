# Control Room (Live Monitor) — Frontend

## Routes

| Path | Inertia component |
|---|---|
| `/control-room` | `resources/js/pages/control-room/index.tsx` (multi-siding overview) |
| `/control-room/{rake}` | `resources/js/pages/control-room/show.tsx` (single-rake deep view) |

Backend controller: `App\Http\Controllers\LiveMonitorController`. Payload shape: see `resources/js/components/control-room/types.ts`.

## Display modes

- Default: light-theme browser view; dark-mode-clean.
- `?display=tv` — TV/wall-mount mode. Larger font scale (1.15×). Multi-siding overview auto-cycles a focused siding every 30s (override with `?cycle=Ns`, min 5s).
- `?theme=dark` — opt-in dark theme for 24/7 control rooms. (Inherits the project's existing dark-mode tokens.)

## Realtime architecture

```
Backend                                    Frontend
=======                                    ========
Operator updates wagon_loading            useControlRoomBroadcast(sidingIds)
  ↓                                       subscribes to private siding.{id}
RakeWagonLoadingUpdated event             channels and listens for:
  ↓                                         · wagon-loading.updated
broadcasts on siding.{id} channel    →      · wagon.weight.updated
                                            · rake-status.updated
                                          ↓
                                          show.tsx merges payload into
                                          local state; components re-render
                                          with Framer Motion transitions

useStaleIndicator (1s tick)               If no event arrives for 60s and
  · live: < 30s since last event          tab is visible, partial Inertia
  · lagging: 30-120s                      reload pulls fresh server snapshot
  · stale: > 120s
```

## Hooks

| Hook | Purpose |
|---|---|
| `use-control-room-broadcast` | Subscribe to multiple `siding.{id}` private channels; track per-siding and global `lastEventAt`. |
| `use-stale-indicator` | Derive `'live' \| 'lagging' \| 'stale'` from `lastEventAt`; auto-reload at 60s when stale + tab visible. |
| `use-tv-mode` | Read `?display=tv&cycle=Ns`; expose `enabled`, `cycleSeconds`, `fontScale`, `activeIndex` (auto-cycling). |

## Component conventions

All components live in `resources/js/components/control-room/`. Each:

1. **Honors `useReducedMotion()`** — when the OS prefers reduced motion, animations become instant. Test by toggling System Preferences → Accessibility → Display → Reduce Motion.
2. **Uses Framer Motion v12** for transitions. Restrict animated properties to `transform` and `opacity` for GPU acceleration.
3. **Uses shadcn/Tailwind tokens** (`bg-card`, `border-border`, `text-foreground`, etc.). Status colors come from the data (`status_color` field on `WagonCard`) — backend is the source of truth.
4. Exports both default and named.

### Adding a new component

1. Create the component in `resources/js/components/control-room/` with a clear single responsibility.
2. Add any new types to `types.ts`.
3. Compose into `index.tsx` (overview) or `show.tsx` (deep view).
4. Verify reduced-motion path manually.
5. Verify dark-mode rendering manually.

## Files

| Path | Purpose |
|---|---|
| `resources/js/pages/control-room/index.tsx` | Multi-siding overview page |
| `resources/js/pages/control-room/show.tsx` | Single-rake deep view page |
| `resources/js/components/control-room/types.ts` | Shared types matching backend payload |
| `resources/js/components/control-room/ControlRoomShell.tsx` | Page shell — header, stale pill, refresh, fullscreen |
| `resources/js/components/control-room/WagonTrain.tsx` | SVG locomotive + wagons; status color tweens; overload pulse |
| `resources/js/components/control-room/LoaderTrucks.tsx` | Loader trucks beneath wagons; spring-glide on wagon change |
| `resources/js/components/control-room/KpiTile.tsx` | Reusable KPI card with count-up numbers |
| `resources/js/components/control-room/SummaryTiles.tsx` | 4-up grid composing KpiTiles |
| `resources/js/components/control-room/LegendStrip.tsx` | Static color-legend strip |
| `resources/js/components/control-room/LoadingProgressDonut.tsx` | SVG donut with animated arc |
| `resources/js/components/control-room/TimeStatusDonut.tsx` | SVG donut + ticking timer |
| `resources/js/components/control-room/AlertsFeed.tsx` | Slide-in alerts with AnimatePresence |
| `resources/js/components/control-room/WagonLoadingTable.tsx` | Sortable table with row highlight on update |
| `resources/js/components/control-room/SidingOverviewCard.tsx` | Multi-siding card composer |
| `resources/js/components/control-room/StaleIndicator.tsx` | Live/lagging/stale pill |
| `resources/js/hooks/use-control-room-broadcast.ts` | Echo subscription |
| `resources/js/hooks/use-stale-indicator.ts` | Status derivation + fallback poll |
| `resources/js/hooks/use-tv-mode.ts` | TV display mode |

## Performance budget

- 60-wagon train + 8 active loaders + 1s timer tick + Framer animations: under 5% CPU on a 2018 mid-tier laptop in idle steady state.
- Avoid `layout` animation on the wagon strip itself (only the loader trucks use `layout`); 60 simultaneous layout-animated elements would tank performance.
- Reduced-motion path entirely bypasses Framer's animation engine.

## Future work

See [ADR-004](../../architecture/ADRs/ADR-004-loadrite-live-ingestion.md) for the planned Loadrite live-ingestion that will turn the dashboard's "lagging" indicator into "live" without UI changes.
