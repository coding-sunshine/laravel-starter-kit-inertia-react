# GeneratePenaltyInsightsAction

## Purpose

Generates AI-powered penalty analysis and actionable recommendations from the last 3 months of penalty data. Designed to run weekly via a scheduled command.

## Location

`app/Actions/GeneratePenaltyInsightsAction.php`

## Method Signature

```php
public function handle(array $sidingIds): ?array
```

## Dependencies

- `App\Services\PrismService` — LLM text generation via Prism

## Parameters

| Parameter | Type | Description |
|-----------|------|-------------|
| `$sidingIds` | `array<int>` | IDs of sidings to analyse |

## Return Value

An array of up to 5 insight objects, or `null` if unavailable:

```php
array<int, array{
    title: string,       // Short heading (< 60 chars)
    description: string, // 1-2 sentence explanation
    severity: string,    // 'high', 'medium', or 'low'
}>
```

Results are cached for **24 hours** keyed by siding IDs.

## Data Aggregated

The action collects 3 months of penalty data broken down by:

- **Type** — penalty type, count, total amount
- **Responsible party** — who caused the penalty
- **Siding** — top 5 sidings by penalty amount
- **Day of week** — identifies patterns in when penalties occur
- **Monthly trend** — 3-month total comparison
- **Dispute outcomes** — disputed vs waived counts

## AI Response Parsing

The AI is instructed to output lines in the format:

```
[SEVERITY] Title | Description
```

These are parsed via regex into the structured array.

## Usage Examples

### From Controller (deferred prop)

```php
'aiInsights' => Inertia::defer(
    fn () => resolve(GeneratePenaltyInsightsAction::class)
        ->handle($sidingIds)
),
```

### From Scheduled Command

```bash
php artisan rrmcs:generate-penalty-insights
```

Scheduled weekly on Mondays at 06:00 in `routes/console.php`.

## Related Components

- **Command**: `GeneratePenaltyInsightsCommand` (`rrmcs:generate-penalty-insights`)
- **Controller**: `PenaltyController@analytics` (deferred prop `aiInsights`)
- **Page**: `resources/js/pages/penalties/analytics.tsx` (`AiInsightsCard`)
- **Model**: `Penalty`, `Rake`, `Siding`
- **Service**: `PrismService`
