# user-email-reset-notification/create

## Purpose

Forgot-password form: email input to request a reset link. Submits to `UserEmailResetNotificationController::store`.

## Location

`resources/js/pages/user-email-reset-notification/create.tsx`

## Route Information

- **URL**: `forgot-password`
- **Route Name**: `password.request` (GET), `password.email` (POST)
- **HTTP Method**: GET (form), POST (submit)
- **Middleware**: `web`, `guest`

## Props (from Controller)

| Prop | Type | Description |
|------|------|-------------|
| `status` | `string?` | Session flash (e.g. success message) |

## User Flow

1. User visits `forgot-password`.
2. Enters email, submits.
3. Reset link sent if account exists; status shown. Link to login.

## Related Components

- **Controller**: `UserEmailResetNotificationController@create`, `UserEmailResetNotificationController@store`
- **Action**: `CreateUserEmailResetNotification`
- **Route**: `password.request`, `password.email`
- **Layout**: `AuthLayout`

## Implementation Details

Uses `UserEmailResetNotificationController.store.form()`, `Form`, `AuthLayout`. Displays `status` when present.
