# tasks/index

## Purpose

Displays a paginated, filterable list of CRM tasks using the DataTable component.

## Location

`resources/js/pages/tasks/index.tsx`

## Props

| Prop | Type | Description |
|------|------|-------------|
| tableData | `DataTableResponse<TasksTableRow>` | Server-side paginated tasks data |
| searchableColumns | `string[]` | List of columns that support full-text search |

## Route

Rendered by `TaskController::index` via route `tasks.index` (`GET /tasks`).

## Related Components

- **Controller**: `TaskController`
- **DataTable**: `TaskDataTable`
- **Layout**: `AppSidebarLayout`
