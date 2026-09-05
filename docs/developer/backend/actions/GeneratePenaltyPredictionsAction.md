# GeneratePenaltyPredictionsAction

## Purpose

Generates AI-powered penalty predictions for the upcoming week per siding, based on 90 days of historical data.

## Location

`app/Actions/GeneratePenaltyPredictionsAction.php`

## Method Signature

```php
public function handle(): int
```

## Dependencies

None (no constructor dependencies).

## Parameters

None. Operates on all sidings that have associated rakes.

## Return Value

`int` - Number of predictions created.

## Usage Examples

### From Command

```php
// Via artisan command: php artisan penalties:predict
(new GeneratePenaltyPredictionsAction)->handle();
```

### From Controller

```php
app(GeneratePenaltyPredictionsAction::class)->handle();
```

## Related Components

- **Agent**: `PenaltyPredictionAgent` - AI agent that generates structured predictions
- **Command**: `GeneratePenaltyPredictionsCommand` (`penalties:predict`)
- **Model**: `PenaltyPrediction` - Stores predictions per siding
- **Tool**: `PredictionsTool` - Chatbot tool that queries predictions

## Notes

- Collects 90-day historical data: weekly trends, by-siding breakdown, by-type+siding, day-of-week patterns, recent root causes
- Deletes existing predictions for today before inserting new ones
- Maps siding names from AI response to siding IDs
- Returns 0 on AI failure (logged as warning)
- Designed to run daily via scheduled command
