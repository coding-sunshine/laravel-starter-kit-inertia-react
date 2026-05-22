# Manager Brief Services

Five services (all in `App\Services\ManagerBrief\`) supply the live widget data
rendered on the manager-brief page. They are called directly from
`ManagerBriefController::index()` on every page load; results are passed as
separate Inertia props so partial reloads can refresh only the widget data.

---

## LiveExposureCalculator

### Purpose

Sums projected rupee overload exposure across rakes currently in `loading` or
`placed` state at a siding. Drives the "Live Rs-at-stake" ticker.

### Location

`app/Services/ManagerBrief/LiveExposureCalculator.php`

### Method Signature

```php
public function handle(int $sidingId): array
// Returns: { total_rs: float, breakdown: list<{rake_id, rake_number, overload_mt, rs}> }
```

### Exclusion Rules

- **Ghost rakes**: skipped when no `wagon_loading` rows AND no `loadrite_events`.
- **Weighbridge source**: rows with `weight_source = 'weighbridge'` are excluded.

### Dependencies

None (no constructor). Reads `Rake`, `wagon_loading`, `loadrite_events`, `wagons`.

---

## OperatorScoreboard

### Purpose

Computes top-5 and bottom-5 loader operators by accuracy percentage over a
configurable day window. Drives the "Operator Scoreboard" widget.

### Location

`app/Services/ManagerBrief/OperatorScoreboard.php`

### Method Signature

```php
public function handle(int $sidingId, int $windowDays = 7): array
// Returns: { top: list<{name, wagons, accuracy_pct, rs_caused}>, bottom: list<...> }
```

### Notes

- Accuracy = 100 × (wagons in 95–100 % PCC band) / total wagons in window.
- Operators with < 10 wagons in the window are excluded (noise filter).
- When fewer than 10 eligible operators exist, returns `floor(N/2)` per side.

### Dependencies

None (no constructor). Reads `wagon_loading`, `rakes`, `wagons` via `DB`.

---

## PendingQueue

### Purpose

Returns counts for the "Pending" widget: unreviewed loading overrides and
force-majeure dispute candidates.

### Location

`app/Services/ManagerBrief/PendingQueue.php`

### Method Signature

```php
public function handle(int $sidingId): array
// Returns: { overrides_pending, overrides_oldest_minutes, disputes_ready, disputes_estimated_rs }
```

### Dependencies

| Dependency | Binding |
|------------|---------|
| `DowntimePenaltyMatcherContract` | bound to `DowntimePenaltyMatcher` |

---

## TrendStrip

### Purpose

Returns three week-over-week delta metrics plus 7-point sparklines for the
trend strip at the bottom of the manager-brief page. Results are cached per
siding for 6 hours.

### Location

`app/Services/ManagerBrief/TrendStrip.php`

### Method Signature

```php
public function handle(int $sidingId): array
// Returns: { penalty_rs, throughput_mt, on_time_dispatch_pct }
// Each key: { current, prior, delta_pct, spark: list<float> }
```

### Cache Key

`manager-brief:trend-strip:{sidingId}:v1` — TTL 21 600 s (6 h).

### Notes

`on_time_dispatch_pct` always returns zeros; `siding_performance` has no
`on_time_pct` column yet. Update this service when that column is added.

### Dependencies

None (no constructor). Reads `SidingPerformance` model via Eloquent.

---

## DowntimePenaltyMatcher

### Purpose

Finds force-majeure candidates by matching Loadrite downtime events against
open DEM reconciliation records for a siding. Used by both `CollectSignals`
(signal type 3) and `PendingQueue`.

### Location

`app/Services/ForceMajeure/DowntimePenaltyMatcher.php`

### Interface

`App\Services\ForceMajeure\Contracts\DowntimePenaltyMatcherContract`

Bound in `AppServiceProvider::register()`.

### Used By

- `App\Actions\ManagerBrief\CollectSignals` — emits `force_majeure` signals
- `App\Services\ManagerBrief\PendingQueue` — populates dispute counts
