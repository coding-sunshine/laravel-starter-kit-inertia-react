# Manager Brief Actions

## Overview

Three actions form the Manager Brief pipeline: `BuildManagerBrief` (orchestrator),
`CollectSignals`, and `RankSignals`. They are called in sequence — either from
`ManagerBriefController::refresh()` directly or via the `manager-brief:refresh` Artisan command.

---

## BuildManagerBrief

### Purpose

Orchestrates the full pipeline: collect → rank → LLM synthesis → Payload DTO.
Cache writes are the caller's responsibility; this action is cache-agnostic.

### Location

`app/Actions/BuildManagerBrief.php`

### Method Signature

```php
public function handle(int $sidingId): Payload
```

### Dependencies

| Dependency | Binding |
|------------|---------|
| `CollectSignals` | concrete |
| `RankSignals` | concrete |
| `ManagerBriefAgent` | concrete (Prism LLM wrapper) |

### Return Value

`App\DataTransferObjects\ManagerBrief\Payload` — contains `actions`, `generatedAt`,
`sidingId`, `modelUsed`, `aiStatus` (`ok` | `failed`), and `failedReason`.

### Usage Example

```php
// From Artisan command / job
$payload = resolve(BuildManagerBrief::class)->handle($sidingId);
Cache::put("manager-brief:{$sidingId}:v1", $payload, 3600);
```

### Related Components

- **Controller**: `ManagerBriefController@refresh`
- **Route**: `manager-brief.refresh` (POST /manager-brief/refresh)
- **Actions**: `CollectSignals`, `RankSignals`
- **Agent**: `App\Ai\Agents\ManagerBriefAgent`

---

## CollectSignals

### Purpose

Queries the database and returns an unsorted `list<Signal>` covering seven
operational signal types for a given siding.

### Location

`app/Actions/ManagerBrief/CollectSignals.php`

### Method Signature

```php
public function handle(int $sidingId): array // list<Signal>
```

### Dependencies

| Dependency | Binding |
|------------|---------|
| `DowntimePenaltyMatcherContract` | bound to `DowntimePenaltyMatcher` in `AppServiceProvider` |

### Signal Types Emitted

| # | Type | Severity Range | Trigger |
|---|------|----------------|---------|
| 1 | `overload_exposure` | medium–critical | loaded_quantity_mt > pcc_weight_mt on active rake |
| 2 | `operator_anomaly` | medium–high | this-week overload rate ≥ 2× 30-day baseline |
| 3 | `force_majeure` | medium | qualifying downtime/DEM overlap via matcher |
| 4 | `scale_silence` | high | Loadrite scale silent > 2 h with active rake present |
| 5 | `pending_override` | medium–high | oldest unreviewed LoadingOverride > 2 h |
| 6 | `underloading_trend` | medium–high | closing_stock_mt WoW drop > 10 % |
| 7 | `demurrage_risk` | high–critical | rake within 3 h of 12-hour placement SLA |

### Related Components

- **Caller**: `BuildManagerBrief`
- **Models**: `Rake`, `LoadriteEvent`, `LoadingOverride`, `SidingPerformance`
- **Service**: `DowntimePenaltyMatcher`

---

## RankSignals

### Purpose

Pure ranking function — scores each Signal with
`rs_at_stake × recencyWeight(recency_minutes) × actionability`, returns top 15.
No database access, no I/O.

### Location

`app/Actions/ManagerBrief/RankSignals.php`

### Method Signature

```php
public function handle(array $signals): array // list<Signal>, max 15
```

### Dependencies

None.

### Recency Decay Weights

| Minutes | Weight |
|---------|--------|
| ≤ 60 | 1.0 |
| ≤ 1440 | 0.8 |
| ≤ 4320 | 0.5 |
| ≤ 10 080 | 0.2 |
| > 10 080 | 0.1 |

### Related Components

- **Caller**: `BuildManagerBrief`
- **Input type**: `App\DataTransferObjects\ManagerBrief\Signal`
