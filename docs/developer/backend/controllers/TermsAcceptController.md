# TermsAcceptController

## Purpose

Handles the terms (and privacy) version acceptance flow. Shows the list of required versions the user must accept and processes the form submission to record acceptances and redirect to the intended URL.

## Location

`app/Http/Controllers/TermsAcceptController.php`

## Actions

- **show** – Renders the `terms/accept` Inertia page with pending required versions and intended URL; redirects to intended if the user has no pending acceptances.
- **store** – Validates that all required version IDs were accepted, calls `RecordTermsAcceptance` for each, then redirects to the intended URL (or dashboard).

## Dependencies

- **Actions**: `GetRequiredTermsVersionsForUser`, `RecordTermsAcceptance`
- **Models**: `TermsVersion`
- **Routes**: `terms.accept` (GET), `terms.accept.store` (POST)

## Related Components

- **Middleware**: `EnsureTermsAccepted` (redirects here when required terms are not accepted)
- **Page**: `terms/accept`
- **Filament**: Terms & Privacy resource (super-admin) for creating/editing terms versions
