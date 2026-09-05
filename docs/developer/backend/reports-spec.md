# Rake Management Reports – Spec

This document lists the reports available in the app and their mapping to the implementation. When the draft spec **"Rake Management Reports (Draft Only).xlsx"** is available, map each tab/sheet to a report key below and add any missing reports to `RunReportAction::REPORT_KEYS` and `RunReportAction::handle()`.

## Current report keys (RunReportAction)

| Key | Name | Description |
|-----|------|-------------|
| `siding_coal_receipt` | Siding Coal Receipt | Shift-wise receipt report |
| `rake_indent` | Rake Indent | Indent history report |
| `txr` | Rake Placement & TXR | TXR performance report |
| `unfit_wagon` | Unfit Wagon Details | Unfit wagon log |
| `wagon_loading` | Wagon Loading Data | Loader-wise loading report |
| `weighment` | In-Motion Weighment | Weighment data report |
| `loader_vs_weighment` | Loader vs Weighment | Overload analysis report |
| `rake_movement` | Rake Movement | Movement delays report |
| `rr_summary` | Railway Receipt (RR) | RR summary report |
| `penalty_register` | Penalty Register | Penalty breakdown report |

## GenerateReports (PDF/Excel-style reports)

`GenerateReports` provides additional narrative-style reports (daily ops, stock movement, rake lifecycle, demurrage analysis, indent fulfillment, vehicle arrival, penalties & reconciliation, performance metrics, financial impact, compliance & audit). These can be mapped to draft tabs as needed.

## UI and export

- **Reports index:** `reports.index` lists all keys with name and description; user selects a report then sets siding and date range.
- **Generate:** POST `reports/generate` with `key`, optional `siding_id`, `date_from`, `date_to`; returns JSON data.
- **Export CSV:** Same endpoint with `export_csv=1` returns a CSV download.

When adding a new report from the draft: add the key to `REPORT_KEYS`, implement the handler in `RunReportAction::handle()`, and optionally add a corresponding method in `GenerateReports` for PDF/Excel output.
