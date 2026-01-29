# user-password/create

## Purpose

Reset-password form (forgot flow): email (read-only), new password, confirmation. Token from URL. Submits to `UserPasswordController::store`.

## Location

`resources/js/pages/user-password/create.tsx`

## Route Information

- **URL**: `reset-password/{token}`
- **Route Name**: `password.reset` (GET), `password.store` (POST)
- **HTTP Method**: GET (form), POST (submit)
- **Middleware**: `web`, `guest`

## Props (from Controller)

| Prop | Type | Description |
|------|------|-------------|
| `token` | `string` | Reset token from URL |
| `email` | `string` | Email from query |

## User Flow

1. User reaches `reset-password/{token}?email=...` from reset link.
2. Sees email (read-only), enters new password and confirmation.
3. Submits; password reset, redirect to login.

## Related Components

- **Controller**: `UserPasswordController@create`, `UserPasswordController@store`
- **Action**: `CreateUserPassword`
- **Route**: `password.reset`, `password.store`
- **Layout**: `AuthLayout`

## Implementation Details

Uses `UserPasswordController.store.form()`, `transform` to add `token` and `email`. `AuthLayout`.
