# Feature Flags

## Purpose

Feature flags are provided by **Laravel Pennant** (`laravel/pennant`) with class-based features. The admin UI for managing flags and segments is **stephenjude/filament-feature-flags**, registered in the Filament admin panel. Resolved flags are exposed to the Inertia frontend via shared props.

## Class-based features

- **Location**: `App\Features\*`
- **Trait**: Use `Stephenjude\FilamentFeatureFlag\Traits\WithFeatureResolver` so the Filament plugin can discover and manage the feature.
- **Default**: Set `public bool $defaultValue = false` (or `true`) on the class, or rely on `config('filament-feature-flags.default')` (default `false`).

Example:

```php
namespace App\Features;

use Stephenjude\FilamentFeatureFlag\Traits\WithFeatureResolver;

final class ExampleFeature
{
    use WithFeatureResolver;

    public bool $defaultValue = false;
}
```

## Admin UI

- **Plugin**: `Stephenjude\FilamentFeatureFlag\FeatureFlagPlugin` in `AdminPanelProvider`.
- **Nav**: Settings → “Manage Features” (or label from `config/filament-feature-flags.php`).
- **Config**: `config/filament-feature-flags.php` — default scope `App\Models\User`, segments (e.g. by email), panel group/label/icon.

Admins can turn a feature on for everyone, or define segments (e.g. by user email) to enable for a subset of users.

## Exposing to Inertia

- **Config**: `config/feature-flags.php` → `inertia_features`: array of `'key' => FeatureClass`.
- **Middleware**: `HandleInertiaRequests` resolves each listed feature for the authenticated user via `Feature::for($user)->active($featureClass)` and adds a `features` prop: `{ key: true|false, ... }`. For guests, `features` is `{}`.

Frontend usage:

```ts
const { features } = usePage().props as { features: Record<string, boolean> };
if (features.example) {
  // show feature
}
```

## Pennant config

- **Config**: `config/pennant.php`
- **Store**: `PENNANT_STORE` — `database` (default) or `array`. Database uses the `features` table (Pennant migration).

## Adding a new feature

1. Create a class in `App\Features\*` with `WithFeatureResolver` and optional `$defaultValue`.
2. Add it to `config/feature-flags.php` under `inertia_features` if the frontend needs it.
3. Run migrations if Pennant is using database; the Filament plugin will list the new feature after discovery.
