# DealForecastController

## Purpose

Returns an AI-powered deal forecast for a specific sale, delegating to `GenerateDealForecastAction`.

## Location

`app/Http/Controllers/DealForecastController.php`

## Routes

| Method | URI | Route Name | Description |
|--------|-----|------------|-------------|
| GET | `/sales/{sale}/forecast` | `sales.forecast` | Get forecast for a sale |

## Response

```json
{
    "sale_id": 1,
    "forecast": {
        "probability": 75,
        "confidence": "medium",
        "reasoning": "...",
        "next_steps": ["..."]
    }
}
```

## Related Components

- **Action**: `GenerateDealForecastAction`
- **Model**: `Sale`
