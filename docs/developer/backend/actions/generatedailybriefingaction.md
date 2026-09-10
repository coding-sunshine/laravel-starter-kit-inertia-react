# GenerateDailyBriefingAction

## Purpose

Generates a short AI-powered daily briefing (3-4 sentences) summarising yesterday's operations for the authenticated user's accessible sidings.

## Location

`app/Actions/GenerateDailyBriefingAction.php`

## Method Signature

```php
public function handle(User $user, array $sidingIds): ?string
```

## Dependencies

- `App\Services\PrismService` — LLM text generation via Prism

## Parameters

| Parameter | Type | Description |
|-----------|------|-------------|
| `$user` | `User` | The authenticated user (used for cache key) |
| `$sidingIds` | `array<int>` | IDs of sidings the user can access |

## Return Value

A trimmed AI-generated briefing string, or `null` if:
- `$sidingIds` is empty
- PrismService is unavailable (no API key configured)
- The LLM call throws an exception

Results are cached for **1 hour** per user (`daily_briefing:{user_id}`).

## Usage Examples

### From Controller (deferred prop)

```php
'aiBriefing' => Inertia::defer(
    fn (): ?string => resolve(GenerateDailyBriefingAction::class)
        ->handle($user, $sidingIds)
),
```

## Data Collected

The action queries the following for yesterday's date:

- Rakes processed (completed loading)
- Penalties incurred (count and amount)
- Month-over-month penalty trend (this month vs last month)
- Pending indents
- Active alerts
- Currently loading rakes

## Related Components

- **Controller**: `ExecutiveDashboardController` (deferred prop `aiBriefing`)
- **Page**: `resources/js/pages/dashboard.tsx` (`AiBriefingCard`)
- **Model**: `Rake`, `Penalty`, `Indent`, `Alert`
- **Service**: `PrismService`
