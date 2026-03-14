# ReportController

## Purpose

Renders the reports index page and individual report pages for reservations, tasks, sales, commissions, login history, and same-device detection.

## Location

`app/Http/Controllers/ReportController.php`

## Methods

| Method | HTTP Method | Route | Purpose |
|--------|------------|-------|---------|
| index | GET | `/reports` | Show grid of available report types |
| show | GET | `/reports/{type}` | Show a specific report with chart + DataTable |

## Routes

- `reports.index`: `GET /reports` - Reports landing page
- `reports.show`: `GET /reports/{type}` - Individual report view

## Related Components

- **Pages**: `reports/index` and `reports/show`
- **DataTables**: `ReservationReportDataTable`, `TaskReportDataTable`, `SaleReportDataTable`, `CommissionReportDataTable`, `LoginHistoryDataTable`
