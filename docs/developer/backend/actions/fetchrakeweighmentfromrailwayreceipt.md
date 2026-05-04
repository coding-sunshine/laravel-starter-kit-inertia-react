# FetchRakeWeighmentFromRailwayReceipt

## Purpose

Creates or merges a `rake_weighments` row from the rake’s uploaded RR wagon snapshots: sums `loaded_weight_mt` for `total_net_weight_mt`, copies the RR PDF into `public` `weighment-pdfs/` for `pdf_file_path`, writes `rake_wagon_weighments`, updates stock (`recordDispatch` vs `applyRakeWeighmentNetDelta`), runs weighment penalties / PLO penalty (same flow as `RakeWeighmentPdfImporter`).

### Per-wagon weights from snapshots

For each `rr_wagon_snapshots` row, wagon weighments are populated from snapshots plus derived fields:

| `rake_wagon_weighments` column | Source |
|-------------------------------|--------|
| `cc_capacity_mt` | `pcc_weight_mt` |
| `printed_tare_mt` | `tare_weight_mt` |
| `actual_gross_mt` | `gross_weight_mt` |
| `net_weight_mt` | `loaded_weight_mt` |
| `over_load_mt` | `overload_weight_mt` (same numeric clamp bounds as RR import: 0–999.99 t); invalid/out-of-range → null |
| `under_load_mt` | **Derived**: RR PDF does not carry under-load explicitly. When both PCC (`pcc_weight_mt`) and loaded net (`loaded_weight_mt`) are present and **loaded &lt; PCC**, `under_load_mt = round(PCC − loaded, 2)`; otherwise null. Uses PCC only (not `permissible_weight_mt`). |

After all wagon rows are inserted, `rake_weighments.total_under_load_mt` and `total_over_load_mt` are set to the **sum** of non-null wagon values (rounded); sums of zero stay **null**. The rake row’s `under_load_mt` / `over_load_mt` are then updated from these totals.

## Location

`app/Actions/FetchRakeWeighmentFromRailwayReceipt.php`

## Method Signature

```php
public function handle(Rake $rake, int $userId): RakeWeighment
```

## Dependencies

- `DuplicateRrPdfToWeighmentStorage`
- `RakeWeighmentPdfImporter` (for `syncWagonsFromRakeWeighment` only)
- `ApplyWeighmentPenaltiesAction`
- `ApplyPloPenaltyAction`
- `UpdateStockLedger`

## Parameters

| Parameter | Type | Description |
|-----------|------|-------------|
| `$rake` | `Rake` | Target rake (must already satisfy siding authorization at the HTTP layer). |
| `$userId` | `int` | Acting user for ledger rows and `created_by` on new weighments. |

## Return Value

The persisted `RakeWeighment` with related wagon weighments loaded.

## Errors

Throws `InvalidArgumentException` when RR selection, totals, PDF media, or existing weighment state violates the same constraints as PDF/XLSX import (e.g. wagon lines already present).

## Related

- Hub / web: `POST weighments/fetch-from-rr` → `WeighmentsController::fetchFromRr`
- API: `POST api/v1/rakes/{rake}/weighments/fetch-from-rr` → `RakeWeighmentWorkflowApiController::fetchFromRr`
- PDF copy helper: `App\Support\DuplicateRrPdfToWeighmentStorage`
