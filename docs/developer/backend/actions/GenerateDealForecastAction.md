# GenerateDealForecastAction

## Purpose

Generates an AI-powered deal forecast for a `Sale` using Prism. Falls back to status-based probability when AI is unavailable. Checks AI credits before calling Prism (cost: `ai_insights` action).

## Location

`app/Actions/GenerateDealForecastAction.php`

## Method Signature

```php
public function handle(Sale $sale): array
```

## Dependencies

- `PrismService` — AI text generation
- `AiCreditService` — credit check (action: `ai_insights`)

## Return Value

```php
[
    'probability' => int,   // 0-100
    'confidence' => string, // low|medium|high
    'reasoning' => string,
    'next_steps' => string[],
]
```

## Related Components

- **Controller**: `DealForecastController`
- **Route**: `sales.forecast` (GET /sales/{sale}/forecast)
