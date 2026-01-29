# SessionController

## Purpose

Handles login (show form, authenticate), logout, and optional redirect to 2FA challenge.

## Location

`app/Http/Controllers/SessionController.php`

## Methods

| Method | HTTP | Route | Purpose |
|--------|------|-------|---------|
| `create` | GET | `login` | Show login form |
| `store` | POST | `login.store` | Authenticate; redirect to 2FA or dashboard |
| `destroy` | POST | `logout` | Log out, invalidate session, redirect home |

## Routes

- `login`: GET `login` — Login form
- `login.store`: POST `login` — Authenticate
- `logout`: POST `logout` — Log out

## Actions Used

None.

## Validation

- `CreateSessionRequest` — Login credentials

## Related Components

- **Page**: `session/create`
- **Routes**: `login`, `login.store`, `logout`
