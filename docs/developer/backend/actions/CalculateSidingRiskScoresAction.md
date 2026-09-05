# CalculateSidingRiskScoresAction

## Purpose

Calculates AI-powered composite risk scores (0-100) for all active sidings based on penalty history and operational performance.

## Location

`app/Actions/CalculateSidingRiskScoresAction.php`

## Method Signature

```php
public function handle(): int
```

## Dependencies

None (no constructor dependencies).

## Parameters

None. Operates on all sidings that have associated rakes.

## Return Value

`int` - Number of risk scores created/updated.

## Usage Examples

### From Controller

```php
app(CalculateSidingRiskScoresAction::class)->handle();
```

### From Command

```php
(new CalculateSidingRiskScoresAction)->handle();
```

## Related Components

- **Agent**: `SidingRiskScoringAgent` - AI agent that generates structured risk scores
- **Model**: `SidingRiskScore` - Stores calculated scores per siding per day
- **Model**: `Siding`, `Penalty`, `SidingPerformance` - Source data

## Notes

- Collects metrics for last 30 days (penalties) and 14 days (performance), plus previous 30-day comparison
- Uses `updateOrCreate` on `(siding_id, calculated_at)` to avoid duplicate scores per day
- Clamps scores to 0-100 range
- Returns 0 on AI failure (logged as warning)
