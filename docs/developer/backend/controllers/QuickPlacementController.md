# QuickPlacementController

## Purpose

Lightweight Inertia controller for siding attendants to capture rake **placement** and **release** times with a single tap. Designed for the Pakur siding workflow but works for any siding the user is authorised to access.

## Location

`app/Http/Controllers/Sidings/QuickPlacementController.php`

## Routes

| Method | URI | Name | Handler |
|--------|-----|------|---------|
| GET | `sidings/{siding}/quick-placement` | `sidings.quick-placement.show` | `show()` |
| POST | `sidings/{siding}/quick-placement` | `sidings.quick-placement.store` | `store()` |

## Methods

### `show(Siding $siding): Response`

Renders the `sidings/quick-placement` Inertia page with up to 50 most recent active rakes for the siding (no `dispatch_time`, ordered by `created_at desc`). Selected fields: `id`, `rake_number`, `placement_time`, `loading_end_time`.

### `store(QuickPlacementRequest $request, Siding $siding): RedirectResponse`

Stamps either `placement_time` or `loading_end_time` on the chosen rake.

- Validates input via `App\Http\Requests\Sidings\QuickPlacementRequest`.
- `event` must be `placed` or `released`.
- Defaults `occurred_at` to `now()` when not supplied.
- Redirects back with a success flash.

## Form Request

`App\Http\Requests\Sidings\QuickPlacementRequest` — validates `rake_id`, `event`, optional `occurred_at`, and authorises the user against the siding.

## Inertia Page

`resources/js/pages/sidings/quick-placement.tsx`

## User Guide

`docs/user-guide/sidings/quick-placement.md`

## Source Spec

`docs/superpowers/specs/2026-05-01-penalty-savings-program-design.md` §6 (Pakur placement gap).
