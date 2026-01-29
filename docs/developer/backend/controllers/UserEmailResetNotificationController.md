# UserEmailResetNotificationController

## Purpose

Shows the forgot-password form and sends a password-reset link via email.

## Location

`app/Http/Controllers/UserEmailResetNotificationController.php`

## Methods

| Method | HTTP | Route | Purpose |
|--------|------|-------|---------|
| `create` | GET | `password.request` | Show forgot-password form |
| `store` | POST | `password.email` | Send reset link |

## Routes

- `password.request`: GET `forgot-password` — Forgot-password form
- `password.email`: POST `forgot-password` — Send reset link

## Actions Used

- `CreateUserEmailResetNotification` — Send reset link

## Validation

- `CreateUserEmailResetNotificationRequest` — Email (and any rate limiting)

## Related Components

- **Page**: `user-email-reset-notification/create`
- **Action**: `CreateUserEmailResetNotification`
- **Routes**: `password.request`, `password.email`
