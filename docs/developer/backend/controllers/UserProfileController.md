# UserProfileController

## Purpose

Shows the profile edit form and updates the authenticated user's profile (e.g. name, email).

## Location

`app/Http/Controllers/UserProfileController.php`

## Methods

| Method | HTTP | Route | Purpose |
|--------|------|-------|---------|
| `edit` | GET | `user-profile.edit` | Show profile edit form |
| `update` | PATCH | `user-profile.update` | Update profile |

## Routes

- `user-profile.edit`: GET `settings/profile` — Edit form
- `user-profile.update`: PATCH `settings/profile` — Update profile

## Actions Used

- `UpdateUser` — Profile update

## Validation

- `UpdateUserRequest` — Profile fields

## Related Components

- **Page**: `user-profile/edit`
- **Action**: `UpdateUser`
- **Routes**: `user-profile.edit`, `user-profile.update`
