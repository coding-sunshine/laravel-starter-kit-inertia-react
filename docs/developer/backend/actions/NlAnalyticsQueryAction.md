# NlAnalyticsQueryAction

## Purpose

Interprets a natural language analytics query using Prism and returns a structured result including an answer, optional data points, chart type suggestion, and SQL hint. Checks AI credits before calling (cost: `nlq_query` action).

## Location

`app/Actions/NlAnalyticsQueryAction.php`

## Method Signature

```php
public function handle(string $query, string $context = 'general'): array
```

## Dependencies

- `PrismService` — AI text generation
- `AiCreditService` — credit check (action: `nlq_query`)

## Return Value

```php
[
    'answer' => string,
    'data' => array,
    'chart_type' => string|null, // bar|line|pie|scatter|null
    'sql_hint' => string|null,
]
```

## Related Components

- **Controller**: `AnalyticsController::nlQuery()`
- **Route**: `analytics.nl-query` (POST /analytics/nl-query)
