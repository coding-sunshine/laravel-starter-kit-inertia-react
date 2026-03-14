# DealTrackerController

**Location**: `app/Http/Controllers/DealTrackerController.php`
**Last Updated**: 2026-03-15

## Routes

| Method | URI | Name | Description |
|--------|-----|------|-------------|
| GET | `deal-tracker` | `deal-tracker.index` | Renders the Kanban + list view page |
| PATCH | `deal-tracker/{reservation}/stage` | `deal-tracker.stage-update` | Updates a reservation's stage |

## Methods

### `index(Request $request): Response`

Renders `deal-tracker/index` with:
- `kanbanColumns` — array of columns (`stage`, `label`, `reservations[]`) for all 6 stages
- `tableData` — from `PropertyReservationDataTable::inertiaProps()`
- `searchableColumns` — searchable column list from the DataTable

### `stageUpdate(Request $request, PropertyReservation $reservation): JsonResponse`

Validates `stage` against allowed values and updates the reservation. Returns `{success: true, stage: string}`.

## Middleware

Requires `auth` + `verified` (inside the main middleware group).
