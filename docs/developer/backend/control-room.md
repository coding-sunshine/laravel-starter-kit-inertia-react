# Control Room (Live Monitor) — Backend

## Purpose

The Control Room dashboard at `/control-room` (and `/control-room/{rake}` for the deep view) renders near-realtime rake-loading state for each siding the user can see. This document describes the backend services that build the page payloads and the broadcast events that drive realtime updates.

## Authorization

- Permission: `sections.live_monitor.view` (defined in `config/section_permissions.php`).
- Route names: `control-room.index`, `control-room.show`.
- Mapped via `route_to_permission` so `AutoPermissionMiddleware` enforces it.
- The deep view also checks per-siding ACL inside `LiveMonitorController::show()`: a user cannot see a rake belonging to a siding they're not attached to (super-admins bypass).

## Data builder

`App\Services\LiveMonitor\LiveMonitorDataBuilder` is the **single source of truth** for the dashboard payload shape. Two methods:

- `forOverview(User $user): array` — multi-siding card view.
- `forRake(Rake $rake): array` — single-rake deep view with full wagon list, loaders, time status, alerts.

Composed of two pure resolvers:

| Resolver | Responsibility |
|---|---|
| `WagonStatusResolver` | Given a `Wagon` + its `RakeWagonLoading` row, returns a `LiveWagonStatus` enum. Pure function. |
| `LoaderActivityResolver` | Given the loaders and recent wagon-loading rows for a rake, derives per-loader `status`, `current_wagon_id`, `last_active_at`. Post-hoc proxy — see ADR-004 for the realtime story. |

### Honest data treatment

The builder never fabricates values. Where the source field is null, the payload returns null and the UI renders `—`. Specifically:

- `rakes.placement_time` is null for many real rakes → `time_status.anchor` falls back to `loading_start_time` or the earliest `wagon_loading.created_at`.
- `wagons.pcc_weight_mt = 0` (real-data quirk on some Pakur wagons as of 2026-05-03) → `LiveWagonStatus::Loading` (cannot promote to Loaded without a valid pcc).
- `wagon_loading.cc_capacity_mt` is null in real data → **never read** by the resolver. Use `wagons.pcc_weight_mt` instead.

### Status derivation rules

`WagonStatusResolver::resolve()` priority order:

1. `wagon.is_unfit` → `Unfit`
2. `loaded ≤ 0` → `Empty`
3. `pcc > 0 && loaded > pcc` → `Overload`
4. `pcc > 0 && loaded ≥ pcc - 0.5` (tolerance) → `Loaded`
5. otherwise → `Loading`

### Loader activity derivation

`LoaderActivityResolver::resolveForRake()` flags a loader as `active` if its most recent `wagon_loading.updated_at` is within `ACTIVITY_WINDOW_MINUTES` (default 10 min). Otherwise `idle`. Active loaders get a `current_wagon_id` and `current_wagon_number`; idle loaders only get `wagons_completed` + `last_active_at`.

This is a post-hoc proxy. The DB does not capture "loader currently positioned at wagon X" — only post-event facts. When Loadrite live ingestion lands (ADR-004), the resolver gains a sub-second-fresh source; the public API of the resolver does not change.

## Broadcast events

The Control Room subscribes to one private channel per visible siding: `siding.{id}`. Three event types fire on this channel:

| Event | Broadcast as | Payload |
|---|---|---|
| `App\Events\RakeWagonLoadingUpdated` | `wagon-loading.updated` | wagon + loader + derived `live_status`/`live_status_color` |
| `App\Events\WagonWeightUpdated` | `wagon.weight.updated` | per-wagon weight from weighbridge or Loadrite |
| `App\Events\RakeStatusUpdated` | `rake-status.updated` | rake-level state change |

`RakeWagonLoadingUpdated` was extended for the Control Room: it now broadcasts on **two** channels — the original `rake-load.{rakeId}` (consumed by the rake-loader workflow page) and the new `siding.{sidingId}` (consumed by the Control Room). Channel auth for `siding.{id}` is in `routes/channels.php`.

## Adding a new field

1. If the field is computed from existing tables: add it to `LiveMonitorDataBuilder` (or one of the resolvers) and update `resources/js/components/control-room/types.ts`.
2. If it's broadcast-driven: add to the appropriate event's `broadcastWith()` and to the corresponding hook in `resources/js/hooks/use-control-room-broadcast.ts`.
3. Write a unit test in `tests/Unit/Services/LiveMonitor/` that exercises the new field with realistic null/edge cases.

## Files

| Path | Purpose |
|---|---|
| `app/Enums/LiveWagonStatus.php` | 5-case enum + label() + color() |
| `app/Services/LiveMonitor/WagonStatusResolver.php` | Pure status derivation |
| `app/Services/LiveMonitor/LoaderActivityResolver.php` | Post-hoc loader activity |
| `app/Services/LiveMonitor/LiveMonitorDataBuilder.php` | Page payload builder |
| `app/Http/Controllers/LiveMonitorController.php` | Inertia controller |
| `app/Events/RakeWagonLoadingUpdated.php` | Broadcast event (extended for siding channel) |
| `routes/channels.php` | `siding.{id}` private channel auth |
| `config/section_permissions.php` | `sections.live_monitor.view` |

## Tests

- `tests/Unit/Services/LiveMonitor/WagonStatusResolverTest.php` — every status path (8 tests).
- `tests/Unit/Services/LiveMonitor/LoaderActivityResolverTest.php` — active/idle/no-rows/sorting (4 tests).
- `tests/Unit/Services/LiveMonitor/LiveMonitorDataBuilderTest.php` — full payload shape, status counts, KPIs, time-status fallback, overview filtering (12 tests).
- `tests/Feature/LiveMonitor/ControlRoomTest.php` — auth, permission, Inertia assertions (6 tests).
- `tests/Feature/LiveMonitor/RakeWagonLoadingBroadcastTest.php` — channel + payload (2 tests).

Total: 32 tests, 124 assertions.
