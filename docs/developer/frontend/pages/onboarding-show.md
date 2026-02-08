# onboarding/show

## Purpose

Single-step onboarding: user sees a welcome message and a “Get started” button. Submitting marks onboarding complete and redirects to the dashboard.

## Location

`resources/js/pages/onboarding/show.tsx`

## Route Information

- **URL**: `onboarding`
- **Route Name**: `onboarding` (GET), `onboarding.store` (POST)
- **Middleware**: `auth`

## Props

| Prop | Type | Description |
|------|------|-------------|
| `status` | `string` | Flash message (e.g. after completion) |

## Related Components

- **Controller**: `OnboardingController@show`, `OnboardingController@store`
- **Action**: `CompleteOnboardingAction`
- **Layout**: `AuthLayout`
