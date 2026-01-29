# dashboard

## Purpose

Authenticated home: placeholder dashboard layout (breadcrumbs, grid, placeholder patterns).

## Location

`resources/js/pages/dashboard.tsx`

## Route Information

- **URL**: `dashboard`
- **Route Name**: `dashboard`
- **HTTP Method**: GET
- **Middleware**: `web`, `auth`, `verified`

## Props (from Controller)

None (route closure renders with no custom props).

## User Flow

1. User visits `dashboard` after login (and email verification).
2. Sees dashboard layout with placeholder content.

## Related Components

- **Route**: `dashboard` (closure in `routes/web.php`)
- **Layout**: `AppLayout` with breadcrumbs

## Implementation Details

Uses `AppLayout`, `PlaceholderPattern`. Breadcrumbs point to dashboard.
