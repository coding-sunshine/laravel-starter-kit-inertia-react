# FunnelController

## Purpose

Renders the conversion funnel view showing lead-to-sale conversion counts across pipeline stages.

## Location

`app/Http/Controllers/FunnelController.php`

## Methods

| Method | HTTP Method | Route | Purpose |
|--------|------------|-------|---------|
| `index` | GET | `/funnel` | Aggregate counts for each funnel stage and render funnel view |

## Routes

- `funnel.index`: `GET /funnel` - Displays conversion funnel chart with stage counts

## Actions Used

None

## Validation

None — read-only endpoint

## Related Components

- **Pages**: `resources/js/pages/funnel/index.tsx` (rendered by this controller)
- **Models**: `Contact`, `PropertyReservation`, `Sale` (queried for stage counts)
- **Routes**: `funnel.index` (defined in routes/web.php)
