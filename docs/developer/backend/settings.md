# Database-backed settings

## Purpose

Application settings are stored in the database using **spatie/laravel-settings** and edited in the Filament admin panel via **filament/spatie-laravel-settings-plugin**. Settings are strongly typed and cached optionally.

## Settings classes

- **Location**: `App\Settings\*`
- **Groups**: Each class defines `group()` (e.g. `app`, `auth`, `seo`). Properties are stored under that group in the `settings` table.
- **Registration**: Settings in `app/Settings` are auto-discovered (see `config/settings.php` → `auto_discover_settings`).

Current groups:

| Class | Group | Purpose |
|-------|--------|---------|
| `AppSettings` | app | Site name, maintenance mode, timezone |
| `AuthSettings` | auth | Registration enabled, email verification required |
| `BillingSettings` | billing | Seat-based billing enabled, allow multiple subscriptions |
| `SeoSettings` | seo | Meta title, meta description, Open Graph image URL |

## Filament UI

- **Pages**: Settings → **App**, **Auth**, **SEO** (under the Settings navigation group).
- **Creation**: `php artisan make:filament-settings-page PageName "App\\Settings\\SettingsClass" --generate`
- Form field names must match the property names on the settings class.

## Usage in app code

Inject the settings class or resolve from the container:

```php
$app = app(App\Settings\AppSettings::class);
$siteName = $app->site_name;

$auth = app(App\Settings\AuthSettings::class);
if (!$auth->registration_enabled) {
    // redirect or abort
}
```

## Migrations

- **Table**: `settings` (created by the package migration).
- **Settings migrations**: Stored in `database/settings/`. Create with `php artisan make:settings-migration SettingsClassName`. In the migration, use `$this->migrator->add('group.property', defaultValue)`.

## Config

- **File**: `config/settings.php`
- **Cache**: `SETTINGS_CACHE_ENABLED` (default `false`). When enabled, settings are cached; clear with `php artisan settings:clear-cache` after changing settings in the DB.
