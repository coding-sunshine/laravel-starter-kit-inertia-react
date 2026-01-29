# user-two-factor-authentication/show

## Purpose

2FA settings: enable/disable 2FA, view recovery codes, QR setup. Rendered by `UserTwoFactorAuthenticationController::show`.

## Location

`resources/js/pages/user-two-factor-authentication/show.tsx`

## Route Information

- **URL**: `settings/two-factor`
- **Route Name**: `two-factor.show`
- **HTTP Method**: GET
- **Middleware**: `web`, `auth`; optionally `password.confirm`

## Props (from Controller)

| Prop | Type | Description |
|------|------|-------------|
| `twoFactorEnabled` | `boolean?` | Whether 2FA is enabled |

## User Flow

1. User visits `settings/two-factor`.
2. Sees status (enabled/disabled), can enable 2FA (QR, manual key, recovery codes) or disable.
3. Setup modal and recovery-code display as applicable.

## Related Components

- **Controller**: `UserTwoFactorAuthenticationController@show`
- **Route**: `two-factor.show`
- **Layout**: `AppLayout`, `SettingsLayout`

## Implementation Details

Uses `useTwoFactorAuth`, `TwoFactorSetupModal`, `TwoFactorRecoveryCodes`. Fortify 2FA endpoints for enable/disable, QR, recovery codes.
