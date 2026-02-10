---
name: pennant-development
description: >-
  Manages feature flags with Laravel Pennant. Activates when creating, checking, or toggling
  feature flags; showing or hiding features conditionally; implementing A/B testing; working with
  @feature directive; or when the user mentions feature flags, feature toggles, Pennant, conditional
  features, rollouts, or gradually enabling features.
---

# Pennant Features

## When to Apply

Activate this skill when:

- Creating or checking feature flags
- Managing feature rollouts
- Implementing A/B testing
- Gating routes, Filament resources, or frontend nav by feature

## This kit: class-based features

This application uses **class-based features** in `App\Features\*`, not string-based `Feature::define()`.

- **Define**: Create a class in `App\Features\` with `Stephenjude\FilamentFeatureFlag\Traits\WithFeatureResolver` and `public bool $defaultValue = true|false`.
- **Config**: Add to `config/feature-flags.php`:
  - `inertia_features`: `'key' => FeatureClass` so the frontend receives `features.key`.
  - `route_feature_map`: `'key' => FeatureClass` if the feature will be used in route middleware.
- **Route middleware**: Use `->middleware('feature:key')` on routes; keys must be in `route_feature_map`. See `EnsureFeatureActive`.
- **Frontend**: `usePage<SharedData>().props.features`; sidebar and settings filter by `features[item.feature]`.
- **Filament**: Override `canAccess()` on a resource and check `Feature::for(auth()->user())->active(FeatureClass::class)`.
- **Impersonation**: `User::canImpersonate()` also requires `ImpersonationFeature` to be active.

Full list of features and gating: **docs/developer/backend/feature-flags.md**. Inventory vs boilerplate: **compare_features.md**.

## General Pennant usage (reference)

### Checking features (class-based)

```php
use App\Features\BlogFeature;
use Laravel\Pennant\Feature;

if (Feature::for($user)->active(BlogFeature::class)) {
    // Feature is active for this user
}
```

### Activating / deactivating (e.g. in tests)

```php
Feature::for($user)->activate(BlogFeature::class);
Feature::for($user)->deactivate(BlogFeature::class);
```

### Blade directive (string-based; kit prefers class-based)

```blade
@feature('new-dashboard')
    <x-new-dashboard />
@endfeature
```

Use `search-docs` for detailed Pennant patterns when using string-based or advanced scenarios.

## Verification

1. New feature class in `App\Features\*` with `WithFeatureResolver` and `$defaultValue`.
2. Added to `config/feature-flags.php` (`inertia_features` and/or `route_feature_map`).
3. Routes/Filament/frontend updated as needed; tests cover feature-on and feature-off.

## Common pitfalls

- Forgetting to add the feature to `route_feature_map` before using `feature:key` middleware.
- Assuming guests get empty `features`; they get each feature’s `$defaultValue`.
- Gating a Filament resource without overriding `canAccess()` (nav still shows; use feature check in `canAccess()`).
