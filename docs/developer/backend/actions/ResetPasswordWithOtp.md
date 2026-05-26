# ResetPasswordWithOtp

## Purpose

Updates a user's password after OTP verification using the reset token issued by `VerifyPasswordResetOtp`.

## Location

`app/Actions/ResetPasswordWithOtp.php`

## Method Signature

```php
public function handle(string $email, string $resetToken, string $password): void
```

## Dependencies

None.

## Parameters

| Parameter | Type | Description |
|-----------|------|-------------|
| `$email` | `string` | User email address |
| `$resetToken` | `string` | Plain-text reset token from verify step |
| `$password` | `string` | New password (validated by form request) |

## Return Value

`void`.

## Usage Examples

### From Controller

```php
app(ResetPasswordWithOtp::class)->handle($email, $resetToken, $password);
```

## Related Components

- **Controller**: `App\Http\Controllers\Api\V1\PasswordResetController`
- **Routes**: `api.v1.auth.reset-password` (POST `api/v1/auth/reset-password`)
- **Model**: `PasswordResetOtp`, `User`
- **Exception**: `PasswordResetOtpException` (`reset_token_invalid`, `reset_token_expired`)

## Notes

- Marks the OTP row as `used` and sets `consumed_at` (single-use reset token).
- Revokes Sanctum `mobile-access` and `mobile-refresh` tokens.
- Dispatches `Illuminate\Auth\Events\PasswordReset`.
