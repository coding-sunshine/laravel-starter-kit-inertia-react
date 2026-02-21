# RecommendDisputeAction

## Purpose

Gets an AI-powered dispute recommendation for a specific penalty, analyzing historical outcomes for similar penalties.

## Location

`app/Actions/RecommendDisputeAction.php`

## Method Signature

```php
public function handle(Penalty $penalty): ?array
```

## Dependencies

None (no constructor dependencies).

## Parameters

| Parameter | Type | Description |
|-----------|------|-------------|
| `$penalty` | `Penalty` | The penalty to evaluate for dispute potential |

## Return Value

`array{should_dispute: bool, confidence: float, estimated_success_probability: float, reasoning: string, recommended_grounds: string[]}|null` - Structured recommendation or null on failure.

## Usage Examples

### From Controller

```php
$recommendation = app(RecommendDisputeAction::class)->handle($penalty);
```

## Related Components

- **Agent**: `DisputeAdvisorAgent` - AI agent with tools and structured output
- **Tool**: `HistoricalDisputeTool` - Queries past dispute outcomes for similar penalties
- **Controller**: `PenaltyController::disputeRecommendation()`
- **Route**: `penalties.dispute-recommendation` (GET `penalties/{penalty}/dispute-recommendation`)

## Notes

- Eager-loads `rake.siding` relationship for context
- The `DisputeAdvisorAgent` uses `HistoricalDisputeTool` to query similar past disputes before making its recommendation
- Returns `null` on AI failure (logged as warning)
- Only recommends disputing when expected value justifies the effort
