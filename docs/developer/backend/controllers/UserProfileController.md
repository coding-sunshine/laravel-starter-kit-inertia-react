# UserProfileController

## Purpose

Shows the profile edit form and updates the authenticated user's profile (name, email, and optional avatar photo).

## Location

`app/Http/Controllers/UserProfileController.php`

## Methods

| Method | HTTP | Route | Purpose |
|--------|------|-------|---------|
| `edit` | GET | `user-profile.edit` | Show profile edit form |
| `update` | PATCH | `user-profile.update` | Update profile (and optional avatar) |

## Routes

- `user-profile.edit`: GET `settings/profile` — Edit form
- `user-profile.update`: PATCH `settings/profile` — Update profile

## Actions Used

- `UpdateUser` — Profile update (name, email, and avatar file when present)

## Validation

- `UpdateUserRequest` — Profile fields: `name`, `email`, and optional `avatar` (image, max 2 MB). See [Media Library (User avatar)](../media-library.md).

## Related Components

- **Page**: `user-profile/edit`
- **Action**: `UpdateUser`
- **Form Request**: `UpdateUserRequest`
- **Routes**: `user-profile.edit`, `user-profile.update`
