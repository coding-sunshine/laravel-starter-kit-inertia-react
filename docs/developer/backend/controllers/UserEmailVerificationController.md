# UserEmailVerificationController

## Purpose

Handles the verification link click (`verify-email/{id}/{hash}`): fulfills verification and redirects to dashboard.

## Location

`app/Http/Controllers\UserEmailVerificationController.php`

## Methods

| Method | HTTP | Route | Purpose |
|--------|------|-------|---------|
| `update` | GET | `verification.verify` | Fulfill verification, redirect to dashboard |

## Routes

- `verification.verify`: GET `verify-email/{id}/{hash}` — Verification link handler (signed)

## Actions Used

None.

## Validation

Uses `EmailVerificationRequest` (signed, throttle).

## Related Components

- **Routes**: `verification.verify`
- **Page**: None (redirect only)
