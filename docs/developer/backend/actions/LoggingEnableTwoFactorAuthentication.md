# LoggingEnableTwoFactorAuthentication

## Purpose

Wraps Fortify's `EnableTwoFactorAuthentication` and logs a `two_factor_enabled` activity on the user after enabling 2FA.

## Location

`app/Actions/Fortify/LoggingEnableTwoFactorAuthentication.php`

## Method Signature

```php
public function __invoke(Model $user, bool $force = false): void
```

## Dependencies

`Laravel\Fortify\Actions\EnableTwoFactorAuthentication` (injected).

## Parameters

| Parameter | Type | Description |
|----------|------|-------------|
| `$user` | `Model` | User model (subject and causer of the activity). |
| `$force` | `bool` | Whether to force enable. |

## Return Value

None.

## Related Components

- **Activity**: `two_factor_enabled` (see [Activity Log](../activity-log.md)).
- **Bound as**: Fortify `EnableTwoFactorAuthentication` in `AppServiceProvider` / Fortify config.
