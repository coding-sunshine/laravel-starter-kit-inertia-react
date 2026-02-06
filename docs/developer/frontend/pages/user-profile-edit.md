# user-profile/edit

## Purpose

Profile settings: update name, email, and optional avatar photo. Optional verification banner and delete-account section. Submits to `UserProfileController::update`.

## Location

`resources/js/pages/user-profile/edit.tsx`

## Route Information

- **URL**: `settings/profile`
- **Route Name**: `user-profile.edit` (GET), `user-profile.update` (PATCH)
- **HTTP Method**: GET (form), PATCH (submit via POST + `_method` when uploading avatar)
- **Middleware**: `web`, `auth`

## Props (from Controller)

| Prop | Type | Description |
|------|------|-------------|
| `status` | `string?` | Session flash (e.g. saved) |
| `auth` | shared | Inertia shared; `auth.user` (includes `avatar`, `avatar_profile`) for default values and preview |

## User Flow

1. User visits `settings/profile`.
2. Sees current avatar (or initials fallback), name, email.
3. Optionally chooses a new photo (JPG, PNG, WebP, GIF; max 2 MB); edits name, email; submits.
4. Profile (and avatar when provided) updated; optional verification notice or resend link. Delete-account UI available.

## Implementation Details

Uses `UserProfileController.update.form()`, `Form` with `encType="multipart/form-data"` and `method="post"` plus hidden `_method="patch"` for file upload compatibility. Avatar preview uses `auth.user.avatar_profile` or `auth.user.avatar` with initials fallback. `AppLayout`, `SettingsLayout`, `DeleteUser`; `auth.user` for initial values; `preserveScroll`. See [Media Library (User avatar)](../../backend/media-library.md).
