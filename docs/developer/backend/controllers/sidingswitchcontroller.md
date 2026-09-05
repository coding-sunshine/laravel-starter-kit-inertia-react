# SidingSwitchController

## Purpose

Handles switching the current user's siding context (RRMCS). Accepts `siding_id`; sets "All sidings" for management users when empty, or a specific siding when the user has access.

## Location

`app/Http/Controllers/SidingSwitchController.php`

## Methods

| Method     | HTTP Method | Route           | Purpose                          |
|-----------|------------|-----------------|----------------------------------|
| __invoke  | POST       | `siding/switch` | Set current siding context and redirect back |

## Routes

- `siding.switch`: POST `siding/switch` — Switch siding context; expects `siding_id` (optional for management).

## Actions Used

None. Uses `SidingContext::set()` and `User::canAccessSiding()` / `User::isManagement()`.

## Validation

No Form Request. Validates via controller: `siding_id` present and valid, user has access to the siding (or is management for "all sidings").

## Related Components

- **Routes**: `siding.switch` (defined in routes/web.php)
- **Services**: `App\Services\SidingContext`, `App\Models\Siding`
