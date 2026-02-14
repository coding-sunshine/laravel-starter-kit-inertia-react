# terms/accept

## Purpose

Displays required terms (and/or privacy) versions that the user must accept before continuing. User checks each document and clicks “I accept”; submitting records acceptances and redirects to the intended URL (or dashboard).

## Location

`resources/js/pages/terms/accept.tsx`

## Route Information

- **URL**: `terms/accept`
- **Route Name**: `terms.accept` (GET), `terms.accept.store` (POST)
- **Middleware**: `auth`, `verified`

## Props

| Prop | Type | Description |
|------|------|-------------|
| `pendingVersions` | `array` | List of required terms versions (id, title, slug, type, type_label, effective_at, summary, body, body_html) |
| `intended` | `string` | URL to redirect to after acceptance (e.g. dashboard or original requested page) |

## Related Components

- **Controller**: `TermsAcceptController@show`, `TermsAcceptController@store`
- **Actions**: `GetRequiredTermsVersionsForUser`, `RecordTermsAcceptance`
- **Middleware**: `EnsureTermsAccepted`
