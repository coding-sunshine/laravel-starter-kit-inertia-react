# LoggingDisableTwoFactorAuthentication

## Purpose

Wraps Fortify's `DisableTwoFactorAuthentication` and logs a `two_factor_disabled` activity on the user after disabling 2FA.

## Location

`app/Actions/Fortify/LoggingDisableTwoFactorAuthentication.php`

## Method Signature

```php
public function __invoke(Model $user): void
```

## Dependencies

`Laravel\Fortify\Actions\DisableTwoFactorAuthentication` (injected).

## Parameters

| Parameter | Type | Description |
|----------|------|-------------|
| `$user` | `Model` | User model (subject and causer of the activity). |

## Return Value

None.

## Related Components

- **Activity**: `two_factor_disabled` (see [Activity Log](../activity-log.md)).
- **Bound as**: Fortify `DisableTwoFactorAuthentication` in `AppServiceProvider` / Fortify config.
