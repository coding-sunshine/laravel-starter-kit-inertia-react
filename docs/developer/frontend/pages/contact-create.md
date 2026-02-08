# contact/create

## Purpose

Public contact form: name, email, subject, message. Submissions are stored and visible in Filament (Contact submissions).

## Location

`resources/js/pages/contact/create.tsx`

## Route Information

- **URL**: `contact`
- **Route Name**: `contact.create` (GET), `contact.store` (POST)
- **HTTP Method**: GET (form), POST (submit)
- **Middleware**: `web` (no auth required)

## Props (from Controller)

| Prop | Type | Description |
|------|------|-------------|
| `flash.status` | `string` | Success message after submit |

## User Flow

1. User visits `contact` (or clicks Contact on welcome).
2. Fills name, email, subject, message (Honeypot fields are hidden).
3. Submits; redirects back with status. Submissions appear in Filament under Engagement → Contact submissions.

## Related Components

- **Controller**: `ContactSubmissionController@create`, `ContactSubmissionController@store`
- **Action**: `StoreContactSubmission`
- **Routes**: `contact.create`, `contact.store`
- **Layout**: `AuthLayout`
- **Component**: `HoneypotFields`
