# CompleteOnboardingAction

## Purpose

Marks the authenticated user as having completed onboarding by setting `onboarding_completed` to true.

## Location

`app/Actions/CompleteOnboardingAction.php`

## Method Signature

```php
public function handle(User $user): void
```

## Parameters

| Parameter | Type | Description |
|-----------|------|-------------|
| `$user` | `User` | The user to mark as onboarded |

## Return Value

None.

## Related Components

- **Controller**: `OnboardingController` (POST onboarding.store)
- **Routes**: `onboarding`, `onboarding.store`
- **Model**: `User` (`onboarding_completed`)
