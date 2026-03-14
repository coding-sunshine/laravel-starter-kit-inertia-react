# reports/show

## Purpose

Individual report detail page showing a summary chart above a DataTable with filters and export.

## Location

`resources/js/pages/reports/show.tsx`

## Route

`GET /reports/{type}` → `ReportController@show`

## Props

```ts
{
  reportType: string;
  reportTitle: string;
  chartData: Array<{ name: string; value: number }>;
  chartType: 'bar' | 'line';
  tableData?: DataTableResponse;
  searchableColumns: string[];
  sharedDevices?: Array<{
    fingerprint_masked: string;
    user_count: number;
    login_count: number;
    last_seen: string;
  }>;
}
```
