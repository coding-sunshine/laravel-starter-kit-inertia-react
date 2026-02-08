# CookieConsentController

## Purpose

Sets the cookie-consent cookie and redirects back. Used when the user clicks "Accept" on the cookie banner.

## Location

`app/Http/Controllers/CookieConsentController.php`

## Methods

| Method | HTTP | Route | Purpose |
|--------|------|-------|---------|
| `__invoke` | GET | `cookie-consent.accept` | Set consent cookie, redirect back |

## Routes

- `cookie-consent.accept`: GET `cookie-consent/accept` — Accept cookies

## Related Components

- **Routes**: `cookie-consent.accept`
- **Config**: `config/cookie-consent.php`
- **Frontend**: `CookieConsentBanner` component
