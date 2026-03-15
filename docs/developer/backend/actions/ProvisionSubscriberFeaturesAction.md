# ProvisionSubscriberFeaturesAction

## Purpose

Activates Pennant feature flags and creates the initial AI credit pool for a newly provisioned subscriber based on their plan's features JSON configuration.

## Location

`app/Actions/ProvisionSubscriberFeaturesAction.php`

## Method Signature

```php
public function handle(User $user, Organization $org, Plan $plan): void
```

## Dependencies

None (no constructor injection required)

## Parameters

| Parameter | Type | Description |
|-----------|------|-------------|
| $user | User | The newly created subscriber |
| $org | Organization | The auto-provisioned organisation |
| $plan | Plan | The selected billing plan |

## Return Value

`void`

## Behaviour

1. Reads `$plan->features['flags']` array of feature class names.
2. For each flag, calls `Feature::for($user)->activate(FullyQualifiedFeatureClass)`.
3. Creates (or updates) an `AiCreditPool` row for the org with credits from `$plan->ai_credits_per_period`.

## Usage

Called from `SignupController::provision()` inside a DB transaction after org creation.
