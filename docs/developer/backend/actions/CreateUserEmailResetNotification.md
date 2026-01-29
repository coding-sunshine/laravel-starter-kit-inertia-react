# CreateUserEmailResetNotification

## Purpose

Sends a password-reset link via Laravel's `Password::sendResetLink` using the given credentials.

## Location

`app/Actions/CreateUserEmailResetNotification.php`

## Method Signature

```php
public function handle(array $credentials): string
```

## Dependencies

None.

## Parameters

| Parameter | Type | Description |
|-----------|------|-------------|
| `$credentials` | `array<string, mixed>` | Typically `['email' => ...]` for reset link |

## Return Value

Status string from `Password::sendResetLink` (e.g. `Password::RESET_LINK_SENT`).

## Usage Examples

### From Controller

```php
$status = app(CreateUserEmailResetNotification::class)->handle($request->only('email'));
```

### From Job/Command

```php
(new CreateUserEmailResetNotification())->handle(['email' => 'user@example.com']);
```

## Related Components

- **Controller**: `UserEmailResetNotificationController`
- **Routes**: `password.request` (GET), `password.email` (POST)
- **Form Request**: `CreateUserEmailResetNotificationRequest`

## Notes

- Uses `Illuminate\Support\Facades\Password`. Configure mail and reset expiry in config.
