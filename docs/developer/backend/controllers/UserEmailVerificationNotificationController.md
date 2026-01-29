# UserEmailVerificationNotificationController

## Purpose

Shows the email verification notice (or redirects if verified) and sends a new verification email on request.

## Location

`app/Http/Controllers/UserEmailVerificationNotificationController.php`

## Methods

| Method | HTTP | Route | Purpose |
|--------|------|-------|---------|
| `create` | GET | `verification.notice` | Show notice or redirect if verified |
| `store` | POST | `verification.send` | Send verification email |

## Routes

- `verification.notice`: GET `verify-email` — Verification notice page
- `verification.send`: POST `email/verification-notification` — Resend verification email

## Actions Used

- `CreateUserEmailVerificationNotification` — Send verification email

## Validation

None (uses `#[CurrentUser]`).

## Related Components

- **Page**: `user-email-verification-notification/create`
- **Action**: `CreateUserEmailVerificationNotification`
- **Routes**: `verification.notice`, `verification.send`
