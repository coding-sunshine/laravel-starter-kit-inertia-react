# VerifyPasswordResetOtp

## Purpose

Validates a pending OTP for the given email and returns a short-lived plain-text reset token for the password update step.

## Location

`app/Actions/VerifyPasswordResetOtp.php`

## Method Signature

```php
public function handle(string $email, string $otp): string
```

## Dependencies

None.

## Parameters

| Parameter | Type | Description |
|-----------|------|-------------|
| `$email` | `string` | User email address |
| `$otp` | `string` | 6-digit verification code |

## Return Value

Plain-text `reset_token` (64 characters) on success.

## Usage Examples

### From Controller

```php
$resetToken = app(VerifyPasswordResetOtp::class)->handle($email, $otp);
```

## Related Components

- **Controller**: `App\Http\Controllers\Api\V1\PasswordResetController`
- **Routes**: `api.v1.auth.verify-reset-otp` (POST `api/v1/auth/verify-reset-otp`)
- **Model**: `PasswordResetOtp`
- **Exception**: `PasswordResetOtpException` (`otp_invalid`, `otp_expired`, `otp_locked`)

## Notes

- Increments `attempts` on invalid OTP; locks the row after `max_attempts`.
- Stores hashed reset token with `reset_token_expires_at` on success.
