# Api\V1\PasswordResetController (API)

## Purpose

Guest API endpoints for mobile forgot-password using email OTP: send code, verify code, reset password.

## Location

`app/Http/Controllers/Api/V1/PasswordResetController.php`

## Methods

| Method | HTTP | Route / name | Purpose |
|--------|------|--------------|---------|
| `sendOtp` | POST | `api/v1/auth/forgot-password` | Send OTP email |
| `verifyOtp` | POST | `api/v1/auth/verify-reset-otp` | Verify OTP, return reset token |
| `reset` | POST | `api/v1/auth/reset-password` | Set new password |

## Routes (guest)

- `api.v1.auth.forgot-password`: POST `api/v1/auth/forgot-password` — throttle `password-reset-otp-send`
- `api.v1.auth.verify-reset-otp`: POST `api/v1/auth/verify-reset-otp` — throttle `password-reset-otp-verify`
- `api.v1.auth.reset-password`: POST `api/v1/auth/reset-password`

## Request bodies

**Forgot password:** `{ "email": "user@example.com" }`

**Verify OTP:** `{ "email": "user@example.com", "otp": "123456" }`

**Reset password:** `{ "email": "user@example.com", "reset_token": "...", "password": "...", "password_confirmation": "..." }`

## Response format

Success responses use plain JSON (`message`, optional `data.reset_token`). Errors return `{ "error": { "code": "...", "message": "..." } }` with HTTP 422.

## Actions Used

- `SendPasswordResetOtp`
- `VerifyPasswordResetOtp`
- `ResetPasswordWithOtp`

## Related

- **Form requests**: `SendPasswordResetOtpRequest`, `VerifyPasswordResetOtpRequest`, `ResetPasswordWithOtpRequest`
- **Config**: `config/auth.php` → `password_reset_otp`
- **Web flow** (unchanged): `UserEmailResetNotificationController` + link-based reset
