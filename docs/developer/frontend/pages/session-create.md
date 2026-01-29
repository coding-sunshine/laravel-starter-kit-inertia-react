# session/create

## Purpose

Login form: email, password, remember me, optional "Forgot password" link. Submits to `SessionController::store`.

## Location

`resources/js/pages/session/create.tsx`

## Route Information

- **URL**: `login`
- **Route Name**: `login` (GET), `login.store` (POST)
- **HTTP Method**: GET (form), POST (submit)
- **Middleware**: `web`, `guest`

## Props (from Controller)

| Prop | Type | Description |
|------|------|-------------|
| `status` | `string?` | Session flash message |
| `canResetPassword` | `boolean` | Whether forgot-password route exists |

## User Flow

1. User visits `login`.
2. Fills email, password; optionally "Remember me".
3. Submits; app authenticates, redirects to 2FA challenge or dashboard.

## Related Components

- **Controller**: `SessionController@create`, `SessionController@store`
- **Route**: `login`, `login.store`
- **Layout**: `AuthLayout`

## Implementation Details

Uses `SessionController.store.form()` (Wayfinder), `Form`, `AuthLayout`. Reset link when `canResetPassword`.
