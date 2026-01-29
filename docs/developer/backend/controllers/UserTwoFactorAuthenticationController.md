# UserTwoFactorAuthenticationController

## Purpose

Shows the 2FA management page (enable/disable, recovery codes) for the authenticated user.

## Location

`app/Http/Controllers/UserTwoFactorAuthenticationController.php`

## Methods

| Method | HTTP | Route | Purpose |
|--------|------|-------|---------|
| `show` | GET | `two-factor.show` | Show 2FA settings page |

## Routes

- `two-factor.show`: GET `settings/two-factor` — 2FA management page

## Actions Used

None.

## Validation

- `ShowUserTwoFactorAuthenticationRequest` — Ensures 2FA feature state is valid

## Related Components

- **Page**: `user-two-factor-authentication/show`
- **Routes**: `two-factor.show`
- **Middleware**: `password.confirm` when 2FA confirmPassword option is enabled
