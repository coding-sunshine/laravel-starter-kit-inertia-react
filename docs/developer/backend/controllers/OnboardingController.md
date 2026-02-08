# OnboardingController

## Purpose

Shows the onboarding page for users who have not completed onboarding and handles the completion action (single-step “Get started” flow).

## Location

`app/Http/Controllers/OnboardingController.php`

## Actions

- **show** – Renders `onboarding/show` Inertia page; redirects to dashboard if already completed.
- **store** – Calls `CompleteOnboardingAction` and redirects to dashboard with status message.

## Dependencies

- **Action**: `CompleteOnboardingAction`
- **Routes**: `onboarding` (GET), `onboarding.store` (POST)

## Related Components

- **Middleware**: `EnsureOnboardingComplete` (excludes these routes)
- **Page**: `onboarding/show`
