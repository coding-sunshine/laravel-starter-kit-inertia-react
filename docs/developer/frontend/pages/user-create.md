# user/create

## Purpose

Registration form: name, email, password, confirmation. Submits to `UserController::store`.

## Location

`resources/js/pages/user/create.tsx`

## Route Information

- **URL**: `register`
- **Route Name**: `register` (GET), `register.store` (POST)
- **HTTP Method**: GET (form), POST (submit)
- **Middleware**: `web`, `guest`

## Props (from Controller)

None.

## User Flow

1. User visits `register`.
2. Fills name, email, password, password confirmation.
3. Submits; user is created, logged in, redirected to dashboard.

## Related Components

- **Controller**: `UserController@create`, `UserController@store`
- **Action**: `CreateUser`
- **Route**: `register`, `register.store`
- **Layout**: `AuthLayout`

## Implementation Details

Uses `UserController.store.form()`, `Form`, `AuthLayout`. Link to login.
