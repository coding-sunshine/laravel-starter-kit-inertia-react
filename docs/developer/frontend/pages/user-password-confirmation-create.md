# user-password-confirmation/create

## Purpose

Password confirmation form: used before sensitive actions (e.g. 2FA setup). User enters current password; submits to Fortify `password.confirm.store`.

## Location

`resources/js/pages/user-password-confirmation/create.tsx`

## Route Information

- **URL**: `user/confirm-password`
- **Route Name**: `password.confirm` (GET), `password.confirm.store` (POST)
- **HTTP Method**: GET (form), POST (submit)
- **Middleware**: `web`, `auth`

## Props (from Controller)

None (Fortify middleware / controller).

## User Flow

1. User is redirected here when a password-confirm-protected action is requested.
2. Enters password, submits.
3. Session confirmed; redirect back to intended action.

## Related Components

- **Route**: `password.confirm`, `password.confirm.store` (Fortify)
- **Layout**: `AuthLayout`

## Implementation Details

Uses `store` from `@/routes/password/confirm`, `Form`, `AuthLayout`. No app controller; Fortify handles flow.
