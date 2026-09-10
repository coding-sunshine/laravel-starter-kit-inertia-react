# SendPasswordResetOtp

## Purpose

Sends a 6-digit OTP by email for the mobile/API forgot-password flow. Throws a validation error when the email is not registered.

## Location

`app/Actions/SendPasswordResetOtp.php`

## Method Signature

```php
public function handle(string $email): void
```

## Dependencies

None.

## Parameters

| Parameter | Type | Description |
|-----------|------|-------------|
| `$email` | `string` | User email address |

## Return Value

`void`.

## Usage Examples

### From Controller

```php
app(SendPasswordResetOtp::class)->handle($request->string('email')->value());
```

## Related Components

- **Controller**: `App\Http\Controllers\Api\V1\PasswordResetController`
- **Routes**: `api.v1.auth.forgot-password` (POST `api/v1/auth/forgot-password`)
- **Notification**: `PasswordResetOtpNotification`
- **Model**: `PasswordResetOtp`

## Notes

- Throws `ValidationException` on `email` with message "The email address is invalid." when no matching `users` row exists.
- Supersedes any existing pending OTP rows for the same email before creating a new one.
- OTP and reset tokens are stored hashed in `password_reset_otps`.
- TTL values come from `config('auth.password_reset_otp')`.
- OTP email is sent **synchronously** (notification does not use the queue); no `queue:work` required for this flow.
