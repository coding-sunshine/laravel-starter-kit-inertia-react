# LoggingGenerateNewRecoveryCodes

## Purpose

Wraps Fortify's `GenerateNewRecoveryCodes` and logs a `recovery_codes_regenerated` activity on the user after regenerating recovery codes.

## Location

`app/Actions/Fortify/LoggingGenerateNewRecoveryCodes.php`

## Method Signature

```php
public function __invoke(Model $user): void
```

## Dependencies

`Laravel\Fortify\Actions\GenerateNewRecoveryCodes` (injected).

## Parameters

| Parameter | Type | Description |
|----------|------|-------------|
| `$user` | `Model` | User model (subject and causer of the activity). |

## Return Value

None.

## Related Components

- **Activity**: `recovery_codes_regenerated` (see [Activity Log](../activity-log.md)).
- **Bound as**: Fortify `GenerateNewRecoveryCodes` in `AppServiceProvider` / Fortify config.
