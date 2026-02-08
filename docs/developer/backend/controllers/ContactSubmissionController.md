# ContactSubmissionController

## Purpose

Shows the contact form and stores submissions (with Honeypot and throttle).

## Location

`app/Http/Controllers/ContactSubmissionController.php`

## Methods

| Method | HTTP | Route | Purpose |
|--------|------|-------|---------|
| `create` | GET | `contact.create` | Show contact form |
| `store` | POST | `contact.store` | Store submission, redirect back with status |

## Routes

- `contact.create`: GET `contact` — Contact form
- `contact.store`: POST `contact` — Submit message (ProtectAgainstSpam)

## Actions Used

- `StoreContactSubmission` — Persist submission

## Validation

- `StoreContactSubmissionRequest` — name, email, subject, message

## Related Components

- **Page**: `contact/create`
- **Actions**: `StoreContactSubmission`
- **Routes**: `contact.create`, `contact.store`
