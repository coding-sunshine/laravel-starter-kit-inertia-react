# legal/privacy

## Purpose

Public Privacy Policy page. Static legal content; linked from the welcome page footer.

## Location

`resources/js/pages/legal/privacy.tsx`

## Route Information

- **URL**: `legal/privacy`
- **Route Name**: `legal.privacy`
- **HTTP Method**: GET
- **Middleware**: `web` (no auth required)

## Props (from Controller)

None (closure route; Inertia render only).

## User Flow

1. User visits `legal/privacy` or clicks "Privacy Policy" in the welcome page footer.
2. Reads policy; can use "Back to home" to return.

## Related Components

- **Route**: `legal.privacy` (closure in `routes/web.php`)
- **Wayfinder**: `@/routes/legal` → `privacy()`
- **Layout**: Custom (sticky header + prose content; no AuthLayout)
