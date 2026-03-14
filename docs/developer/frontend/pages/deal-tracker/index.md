# Deal Tracker — Index Page

**Location**: `resources/js/pages/deal-tracker/index.tsx`
**Route**: `GET /deal-tracker` (`deal-tracker.index`)
**Last Updated**: 2026-03-15

## Purpose

Provides a visual deal pipeline for property reservations. Supports two views:

- **Kanban view** (default): drag-and-drop cards grouped by stage with inline stage updates
- **List view**: tabular display of the same data via the DataTable response

## Props

| Prop | Type | Description |
|------|------|-------------|
| `kanbanColumns` | `KanbanColumn[]` | 6 columns (enquiry → settled) with reservations |
| `tableData` | `TableData` | DataTable response from `PropertyReservationDataTable` |
| `searchableColumns` | `string[]` | Columns available for search in list view |

## Kanban Stages

1. `enquiry` — initial contact
2. `qualified` — buyer is qualified
3. `reservation` — reservation placed
4. `contract` — contracts exchanged
5. `unconditional` — unconditional contract
6. `settled` — deal settled

## Features

- Drag-and-drop cards between columns
- Stage change fires `PATCH /deal-tracker/{id}/stage` via Inertia router
- Optimistic UI update: card moves immediately, rolled back on failure
- `data-pan="deal-tracker-tab"` on page heading
- `data-pan="deal-tracker-kanban"` on Kanban toggle button
- Cards show: contact ID, project/lot, purchase price, days in stage, deposit status

## Related

- Controller: `DealTrackerController`
- DataTable: `PropertyReservationDataTable`
