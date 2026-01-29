# UserController

## Purpose

Handles registration (show form, create user) and account deletion.

## Location

`app/Http/Controllers/UserController.php`

## Methods

| Method | HTTP | Route | Purpose |
|--------|------|-------|---------|
| `create` | GET | `register` | Show registration form |
| `store` | POST | `register.store` | Create user, log in, redirect to dashboard |
| `destroy` | DELETE | `user.destroy` | Delete current user, log out, redirect home |

## Routes

- `register`: GET `register` — Registration form
- `register.store`: POST `register` — Create account
- `user.destroy`: DELETE `user` — Delete account

## Actions Used

- `CreateUser` — Registration
- `DeleteUser` — Account deletion

## Validation

- `CreateUserRequest` — Registration
- `DeleteUserRequest` — Deletion (e.g. password confirmation)

## Related Components

- **Page**: `user/create`
- **Actions**: `CreateUser`, `DeleteUser`
- **Routes**: `register`, `register.store`, `user.destroy`
