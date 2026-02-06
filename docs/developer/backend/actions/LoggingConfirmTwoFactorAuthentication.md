# LoggingConfirmTwoFactorAuthentication

## Purpose

Wraps Fortify's `ConfirmTwoFactorAuthentication` and logs a `two_factor_confirmed` activity on the user after confirming 2FA with a code.

## Location

`app/Actions/Fortify/LoggingConfirmTwoFactorAuthentication.php`

## Method Signature

```php
public function __invoke(Model $user, string $code): void
```

## Dependencies

`Laravel\Fortify\Actions\ConfirmTwoFactorAuthentication` (injected).

## Parameters

| Parameter | Type | Description |
|----------|------|-------------|
| `$user` | `Model` | User model (subject and causer of the activity). |
| `$code` | `string` | TOTP code. |

## Return Value

None.

## Related Components

- **Activity**: `two_factor_confirmed` (see [Activity Log](../activity-log.md)).
- **Bound as**: Fortify `ConfirmTwoFactorAuthentication` in `AppServiceProvider` / Fortify config.
