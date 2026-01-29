# user-password/edit

## Purpose

Change-password form (authenticated): current password, new password, confirmation. Submits to `UserPasswordController::update`.

## Location

`resources/js/pages/user-password/edit.tsx`

## Route Information

- **URL**: `settings/password`
- **Route Name**: `password.edit` (GET), `password.update` (PUT)
- **HTTP Method**: GET (form), PUT (submit)
- **Middleware**: `web`, `auth`; `throttle:6,1` on update

## Props (from Controller)

None.

## User Flow

1. User visits `settings/password`.
2. Enters current password, new password, confirmation.
3. Submits; password updated, form reset.

## Related Components

- **Controller**: `UserPasswordController@edit`, `UserPasswordController@update`
- **Action**: `UpdateUserPassword`
- **Route**: `password.edit`, `password.update`
- **Layout**: `AppLayout`, `SettingsLayout`

## Implementation Details

Uses `UserPasswordController.update.form()`, `Form`, `AppLayout`, `SettingsLayout`. Refocus on error, `preserveScroll`, `resetOnSuccess`.
