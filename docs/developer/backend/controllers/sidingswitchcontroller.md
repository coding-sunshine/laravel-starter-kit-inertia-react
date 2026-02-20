# SidingSwitchController

## Purpose

Handles switching the user's active siding context (session-level siding scoping for RRMCS pages).

## Location

`app/Http/Controllers/SidingSwitchController.php`

## Methods

| Method | HTTP Method | Route | Purpose |
|--------|------------|-------|---------|
| __invoke | POST | `/siding/switch` | Switch the user's active siding or clear to "All sidings" |

## Routes

- `siding.switch`: `POST /siding/switch` - Accepts `siding_id` (nullable int). When null, clears siding context to show all sidings (super-admin/management only).

## Actions Used

None. Uses `SidingContext::set()` directly.

## Validation

Inline validation:
- `siding_id`: nullable integer, must exist in `sidings` table
- Additional check: user must have access via `canAccessSiding()`
- Only super-admin or management roles may set `siding_id` to null ("All sidings")

## Related Components

- **Services**: `SidingContext` (sets the active siding in session)
- **Frontend**: `siding-switcher.tsx` (React dropdown that posts to this endpoint)
- **Middleware**: `SetSidingContext` (restores siding context from session on each request)
- **Routes**: `siding.switch` (defined in routes/web.php)
