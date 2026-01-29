# user-two-factor-authentication-challenge/show

## Purpose

2FA challenge during login: OTP or recovery code entry. Submits to Fortify `two-factor.login.store`.

## Location

`resources/js/pages/user-two-factor-authentication-challenge/show.tsx`

## Route Information

- **URL**: `two-factor-challenge`
- **Route Name**: `two-factor.login` (GET), `two-factor.login.store` (POST)
- **HTTP Method**: GET (form), POST (submit)
- **Middleware**: `web`, `guest`

## Props (from Controller)

None (Fortify).

## User Flow

1. After login, if 2FA enabled, user is redirected here.
2. Enters authentication code or switches to recovery code.
3. Submits; app verifies, completes login, redirects to dashboard.

## Related Components

- **Route**: `two-factor.login`, `two-factor.login.store` (Fortify)
- **Layout**: `AuthLayout`

## Implementation Details

Uses `store` from `@/routes/two-factor/login`, `Form`, `InputOTP`, `AuthLayout`. Toggle between OTP and recovery-code mode.
