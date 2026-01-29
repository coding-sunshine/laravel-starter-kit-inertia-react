# user-profile/edit

## Purpose

Profile settings: update name, email. Optional verification banner and delete-account section. Submits to `UserProfileController::update`.

## Location

`resources/js/pages/user-profile/edit.tsx`

## Route Information

- **URL**: `settings/profile`
- **Route Name**: `user-profile.edit` (GET), `user-profile.update` (PATCH)
- **HTTP Method**: GET (form), PATCH (submit)
- **Middleware**: `web`, `auth`

## Props (from Controller)

| Prop | Type | Description |
|------|------|-------------|
| `status` | `string?` | Session flash (e.g. saved) |
| `auth` | shared | Inertia shared; `auth.user` for default values |

## User Flow

1. User visits `settings/profile`.
2. Edits name, email; submits.
3. Profile updated; optional verification notice or resend link. Delete-account UI available.

## Related Components

- **Controller**: `UserProfileController@edit`, `UserProfileController@update`
- **Action**: `UpdateUser`
- **Route**: `user-profile.edit`, `user-profile.update`
- **Layout**: `AppLayout`, `SettingsLayout`

## Implementation Details

Uses `UserProfileController.update.form()`, `Form`, `AppLayout`, `SettingsLayout`, `DeleteUser`. `auth.user` for initial values; `preserveScroll`.
