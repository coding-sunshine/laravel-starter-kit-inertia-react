# RunReportAction

## Purpose

Runs operational and core grid reports scoped to the authenticated user’s accessible sidings—returning arrays for CSV/XLSX export or paginated JSON for `/reports/generate`.

## Location

`app/Actions/RunReportAction.php`

## Report keys

- **`RAKE_MANAGEMENT_REPORT_KEYS`**: Operational reports (coal receipt through penalty register).
- **`COAL_LOGESTIC_CORE_REPORT_KEYS`**: Core reports on the Coal Logestic Core sidebar (**`rail_dispatch_dpr`**, **`penalty_report`**, **`overloading_report`**, **`underloading_report`**, **`loader_performance_report`**).

`REPORT_KEYS` holds UI metadata (`name`, `description`) for all configured keys including **`rail_dispatch_dpr`**, **`overloading_report`**, **`underloading_report`**, and **`loader_performance_report`**.

## Method signatures

```php
public function handle(string $key, array $sidingIds, array $params = []): array
```

```php
public function handlePaginated(string $key, array $sidingIds, array $params, int $page, int $perPage): array
```

```php
public static function reportGenerateKeys(): array
```

## Dependencies

None (readonly class with no constructor).

## Parameters

### `handle` / `handlePaginated`

| Parameter    | Type     | Description                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                 |
| ------------ | -------- | ------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| `$key`       | `string` | Report key (must be in `reportGenerateKeys()` for the grid, or other keys for delegated/legacy flows).                                                                                                                                                                                                                                                                                                                                                                                                                                      |
| `$sidingIds` | `int[]`  | Siding IDs the user may see; rows are restricted to rakes on these sidings.                                                                                                                                                                                                                                                                                                                                                                                                                                                                 |
| `$params`    | `array`  | Optional filters: `siding_id`, `date_from`, `date_to`, `rake_number`, `loader` (where applicable), `power_plant_id` (Rail Dispatch DPR), `penalty_stage` (`pre_rr` \| `post_rr`, **Penalty Report** only), `underload_threshold_percent` (0–100, **Underloading Report** / **Loader Performance** underload rule, default **1**), **`loader_id`** / **`loader_operator_id`** (**Loader Performance** POST; operator resolved to `loader_operator_name`), plus internal `grid_pagination`, `grid_offset`, `grid_limit`, `no_limit`, `limit`. |

### Rail Dispatch DPR (`rail_dispatch_dpr`)

- One **row per `RrDocument`** (primary `diverrt_destination_id` null and diversion legs linked to `DiverrtDestination`).
- Date filter uses **`rake.loading_date`** (same as RR Summary).
- Rows include **`_row_highlight`** (`'diversion'` when the rake is diverted or has multiple RR legs), **`_rr_document_id`**, and **`Remarks`** (`Diverted to …` on diversion legs).
- Charge amounts use the same **`RakeCharge`** scoping as RR Summary (`is_actual_charges` + `diverrt_destination_id` aligned to the row’s RR).

### Penalty Report (`penalty_report`, Coal Logestic Core)

- Same merge as **`penalty_register`**: **`AppliedPenalty`** (Pre-RR) + **`RrPenaltySnapshot`** (Post-RR), filtered by rake **`loading_date`**, siding, rake number.
- **`AppliedPenalty`** rows are excluded for any rake that already has **`rr_penalty_snapshots`** — the actual RR penalty supersedes the prediction, and listing both double-counted the rake in rows and totals.
- Column names match the Coal Logestic spec (e.g. **Penalty Amount**, **Overload Qty**, **Delay Time**, **Stage (Pre/Post RR)**).
- Optional **`penalty_stage`**: `pre_rr` or `post_rr` to filter one stream.

### Overloading Report (`overloading_report`, Coal Logestic Core)

- One row per **`rake_wagon_weighments`** row with **`over_load_mt` > 0**, joined to **`rake_weighments`** → **`rakes`** → **`sidings`**.
- Date filters use **`rake.loading_date`** (same as Inmotion Weighment). Display **Date** prefers **`weighment_time`** (date part), else **`rake.loading_date`**.
- **Loader ID** / **Loader Operator** come from the latest **`wagon_loading`** per (**`rake_id`**, **`wagon_id`**) using **`MAX(id)`**, left joined; loader display prefers **`loaders.code`**, then **`loader_name`**, then numeric **`loaders.id`**.
- Optional **`loader`**: same as **`wagon_loading`**—numeric **`loader_id`** or substring match on **`loader_name`** / **`code`** on the joined latest **`wagon_loading`**; rows with no matching loader context are excluded when this filter is set.
- **Actual Weight (MT)** is in-motion **net** (`net_weight_mt`).
- **Penalty Impact** is **`Overload penalty`** for every included row.
- **Remarks** use **`rake_wagon_weighments.action_taken`** when set.

### Underloading Report (`underloading_report`, Coal Logestic Core)

- Aligns with **rake performance** modal weighment wagons: include when **`under_load_mt` > 0**, **`COALESCE(cc_capacity_mt, wagons.pcc_weight_mt)` > 0**, and **(under_load_mt × 100 / CC) ≥ `underload_threshold_percent`** (default **1**, clamped 0–100). Wagons with **`over_load_mt` > 0** are excluded.
- Same joins as **`overloading_report`** (latest **`wagon_loading`** per rake+wagon for **Loader ID**).
- Optional **`loader`**: same behavior as **`overloading_report`** / **`wagon_loading`**.
- **CC Capacity** uses that **COALESCE**; **Actual Weight** is **`net_weight_mt`**; **Underload Qty** is **`under_load_mt`**; **Loss Impact** is **`Capacity shortfall`**.

### Loader Performance (`loader_performance_report`, Coal Logestic Core)

- One row per **`loaders.id`** with activity in range: aggregates from **`wagon_loading`** joined **`rakes`** (dates on **`rake.loading_date`**) and **`wagons`** for effective CC **`COALESCE(wagon_loading.cc_capacity_mt, wagons.pcc_weight_mt)`**.
- Only **`wagon_loading`** rows with non-null **`loader_id`**; joins exclude soft-deleted **`loaders`**.
- **Overload** / **underload** definitions match **`LoaderOverloadMetricsService`** (underload counts when shortfall as % of CC ≥ **`underload_threshold_percent`**, default **1**, clamped 0–100).
- **Columns (export / API keys)**: **`Loader`**, **`Siding`**, **`Wagons`** (eligible wagon count), **`Overload`**, **`Underload`**, **`Avg MT`**, **`Dev MT`** (mean absolute load vs CC in MT), **`Accuracy`** (percentage string, e.g. **`100%`**, **`33.3%`**; **`—`** when no eligible wagons).

### RR Wagon Details (`rr_wagon_details_report`, Coal Logestic Advance)

- One row per `rr_wagon_snapshots` line joined to rake + RR (weights, chargeable allocation, normal/punitive rates).
- Punitive rate per wagon is picked from `RrPenaltySnapshot` rows for the wagon's RR document via `punitiveRatePickFromMaps()`, matching by `wagon_number`, falling back to `wagon_sequence`, falling back to a whole-RR ("broad") bucket — see `punitiveRateMapsForRrSnapshots()`.
- When a wagon/sequence/broad bucket has snapshots for multiple penalty codes, `pickPreferredOverloadPenaltyCodeRates()` prefers `penaltyTypeCodes()` order, else the first finite rate found.

### Penalty type codes (`penaltyTypeCodes()`)

Used by the RR Wagon Details punitive-rate lookup above. Sourced dynamically from the `penalty_types` table (`PenaltyType::query()->orderBy('id')->pluck('code')`) rather than a hardcoded list, memoized per action run via `once()`. A fixed preferred-order prefix — `POL1, POLA, PLO, PCLA, ENHC` — is placed first (intersected with what actually exists in `penalty_types`) so the historical preference order for picking an overload penalty rate holds regardless of table row order; all other configured codes follow.

## Return value

- **`handle`**: `array<int, array<string, mixed>>` — list of associative row arrays (column labels as keys).
- **`handlePaginated`**: `array{data: array, meta: array{current_page, per_page, total, last_page}}`.

## Usage examples

### From `ReportsController::generate`

```php
resolve(RunReportAction::class)->handlePaginated($key, $sidingIds, $params, $page, $perPage);
```

### Export (full dataset)

```php
$params['no_limit'] = true;
resolve(RunReportAction::class)->handle('rail_dispatch_dpr', $sidingIds, $params);
```

## Related components

- **Controller**: `App\Http\Controllers\Reports\ReportsController`
- **Routes**: `reports.index` (GET), `reports.generate` (POST)
- **Models**: `RakeWagonWeighment`, `WagonLoading`, `Loader`, `LoaderOperator`, `RrDocument`, `Rake`, `RakeCharge`, `PowerPlant`, `DiverrtDestination`, `RrPenaltySnapshot`, `PenaltyType`, and others per report key

## Notes

- Grid pagination is capped at 60 rows per request in the controller; the action applies `grid_offset` / `grid_limit` when `grid_pagination` is set.
- `penalty_register` uses a custom merge-and-slice pagination path in `handlePaginated`.
