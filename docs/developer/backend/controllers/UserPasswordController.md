# UserPasswordController

## Purpose

Handles forgot-password (form + reset via token) and authenticated password change (edit form + update).

## Location

`app/Http/Controllers/UserPasswordController.php`

## Methods

| Method | HTTP | Route | Purpose |
|--------|------|-------|---------|
| `create` | GET | `password.reset` | Show reset form (token in URL) |
| `store` | POST | `password.store` | Reset password via token |
| `edit` | GET | `password.edit` | Show change-password form (authenticated) |
| `update` | PUT | `password.update` | Update authenticated user's password |

## Routes

- `password.reset`: GET `reset-password/{token}` — Forgot-password reset form
- `password.store`: POST `reset-password` — Perform reset
- `password.edit`: GET `settings/password` — Change-password form
- `password.update`: PUT `settings/password` — Update password

## Actions Used

- `CreateUserPassword` — Reset via token
- `UpdateUserPassword` — Change password when authenticated

## Validation

- `CreateUserPasswordRequest` — Reset credentials
- `UpdateUserPasswordRequest` — New password (e.g. current + new)

## Related Components

- **Pages**: `user-password/create`, `user-password/edit`
- **Actions**: `CreateUserPassword`, `UpdateUserPassword`
- **Routes**: `password.reset`, `password.store`, `password.edit`, `password.update`
