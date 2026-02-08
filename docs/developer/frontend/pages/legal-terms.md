# legal/terms

## Purpose

Public Terms of Service page. Static legal content; linked from the welcome page footer.

## Location

`resources/js/pages/legal/terms.tsx`

## Route Information

- **URL**: `legal/terms`
- **Route Name**: `legal.terms`
- **HTTP Method**: GET
- **Middleware**: `web` (no auth required)

## Props (from Controller)

None (closure route; Inertia render only).

## User Flow

1. User visits `legal/terms` or clicks "Terms of Service" in the welcome page footer.
2. Reads terms; can use "Back to home" to return.

## Related Components

- **Route**: `legal.terms` (closure in `routes/web.php`)
- **Wayfinder**: `@/routes/legal` → `terms()`
- **Layout**: Custom (sticky header + prose content; no AuthLayout)
