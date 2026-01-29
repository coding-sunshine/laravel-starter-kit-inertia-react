# user-email-verification-notification/create

## Purpose

Email verification notice: message and "Resend verification email" button. Shown when user is unverified. Submits to `UserEmailVerificationNotificationController::store`.

## Location

`resources/js/pages/user-email-verification-notification/create.tsx`

## Route Information

- **URL**: `verify-email`
- **Route Name**: `verification.notice` (GET), `verification.send` (POST)
- **HTTP Method**: GET (page), POST (resend)
- **Middleware**: `web`, `auth`

## Props (from Controller)

| Prop | Type | Description |
|------|------|-------------|
| `status` | `string?` | `verification-link-sent` when resend succeeded |

## User Flow

1. User hits `verify-email` when unverified (e.g. after registration).
2. Sees notice, can click "Resend verification email".
3. On resend, status updated; link to log out.

## Related Components

- **Controller**: `UserEmailVerificationNotificationController@create`, `UserEmailVerificationNotificationController@store`
- **Action**: `CreateUserEmailVerificationNotification`
- **Route**: `verification.notice`, `verification.send`
- **Layout**: `AuthLayout`

## Implementation Details

Uses `UserEmailVerificationNotificationController.store.form()`, `Form`, `AuthLayout`. Shows success message when `status === 'verification-link-sent'`.
