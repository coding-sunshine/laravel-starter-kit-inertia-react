# PipelineController

## Purpose

Renders the sales pipeline Kanban/list view, grouping sales by status.

## Location

`app/Http/Controllers/PipelineController.php`

## Methods

| Method | HTTP Method | Route | Purpose |
|--------|------------|-------|---------|
| `index` | GET | `/pipeline` | Fetch all sales grouped by status and render pipeline view |

## Routes

- `pipeline.index`: `GET /pipeline` - Displays the Kanban and list view of sales grouped by status

## Actions Used

None

## Validation

None — read-only endpoint

## Related Components

- **Pages**: `resources/js/pages/pipeline/index.tsx` (rendered by this controller)
- **Models**: `Sale` (queried to build grouped view)
- **Routes**: `pipeline.index` (defined in routes/web.php)
