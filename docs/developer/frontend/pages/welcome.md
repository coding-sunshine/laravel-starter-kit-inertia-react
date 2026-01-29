# welcome

## Purpose

Landing page: hero, feature highlights, and nav links (Log in, Register, or Dashboard when authenticated).

## Location

`resources/js/pages/welcome.tsx`

## Route Information

- **URL**: `/`
- **Route Name**: `home`
- **HTTP Method**: GET
- **Middleware**: (none)

## Props (from Controller)

| Prop | Type | Description |
|------|------|-------------|
| `auth` | shared | Inertia shared; `auth.user` when logged in |

## User Flow

1. User visits `/`.
2. Sees welcome content; nav shows Log in / Register or Dashboard when authenticated.
3. Can navigate to login, register, or dashboard.

## Related Components

- **Route**: `home` (closure in `routes/web.php`)
- **Layout**: Standalone (no AppLayout)

## Implementation Details

Uses `usePage<SharedData>().props.auth`. Links use Wayfinder (`dashboard`, `login`, `register`).
