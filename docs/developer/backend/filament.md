# Filament Admin Panel

## Purpose

The Filament admin panel at `/admin` provides a backend UI for users with **super-admin** or **admin** roles. It is separate from the main Inertia/React app.

## Auth

- **Spatie Laravel Permission**: Roles `super-admin`, `admin`, and `user`. Permission `access admin panel` plus resource-level permissions (`view users`, `create users`, `edit users`, `delete users`).
- **Panel access**: `FilamentUser::canAccessPanel` checks `$user->can('access admin panel')`. Super-admins bypass via `Gate::before` in `AppServiceProvider`; admins have the permission.
- **Guard**: `web` (same as Fortify).

## Dev credentials

For local development, use seeded user **admin@example.com** / **password** (role `super-admin`). Log in at `/admin`.

## Creating resources

```bash
php artisan make:filament-resource Model --generate --view
```

Use **policies** and **permissions** for authorization. Prefer `$user->can(...)` in policies; super-admin is handled by `Gate::before`.

## Generators

- `php artisan make:filament-resource Model --generate --view` — Resource with form, infolist, table, pages.
- `php artisan make:filament-relation-manager ResourceName relationName --attach` — Relation manager for a resource.
- `php artisan make:filament-widget WidgetName` — Generic widget; add `--stats-overview` for stats, `--chart`, or `--table`.
- `php artisan make:filament-page PageName` — Custom Filament page.

## Config

- **Panel**: `app/Providers/Filament/AdminPanelProvider.php` (path, guard, login, branding, global search, dark mode, max width, database notifications).
- **Filament**: `config/filament.php`.

## DX features

- **Branding**: `brandName`, `brandLogo`, `favicon` in `AdminPanelProvider`; app name and `public/logo.svg`, `public/favicon.svg` by default.
- **Global search**: Panel `globalSearch()`; resources override `getGloballySearchableAttributes()` (e.g. `UserResource`: `['name', 'email']`).
- **Dark mode**: `darkMode()` on panel; users can toggle.
- **Dashboard widget**: `App\Filament\Widgets\StatsOverviewWidget` (e.g. user count with link to users list); discovered via `app/Filament/Widgets`.
- **Table defaults**: Default sort, per-page options, search debounce — e.g. `UsersTable` (`defaultSort`, `paginationPageOptions`, `searchDebounce`).
- **Database notifications**: `databaseNotifications()` on panel; `notifications` table required (`php artisan notifications:table` + migrate).

## Testing

Use the `actsAsFilamentAdmin(TestCase $test, string $role = 'admin'): User` helper in Pest feature tests when you need an authenticated admin or super-admin. It seeds `RolesAndPermissionsSeeder`, creates a user with the given role, calls `$this->actingAs($user)`, and returns the user. Example:

```php
actsAsFilamentAdmin($this);
$response = $this->get('/admin');
$response->assertOk();
```

See `tests/Feature/Filament/AdminPanelAccessTest.php` and `tests/Pest.php` for the helper definition.

## Deploy

For production, run `composer run optimize-production` (or `php artisan config:cache`, `route:cache`, `filament:cache-components` in your deploy pipeline). Do not add `filament:cache-components` to `post-autoload-dump` or `post-update-cmd` so local dev stays unchanged.

## Filament Blueprint (optional)

Filament Blueprint is a premium Laravel Boost extension that helps AI agents produce detailed Filament implementation plans. The project works without it.

**To install:** Set `FILAMENT_BLUEPRINT_EMAIL` and `FILAMENT_BLUEPRINT_LICENSE_KEY` in `.env` (see `.env.example`), then run:

```bash
composer setup-blueprint
```

Or run `scripts/setup-filament-blueprint.sh` directly. When prompted during `boost:install`, select **Filament Blueprint**. If the env vars are missing, the script skips install and exits 0.

## Links

- [Filament v5 docs](https://filamentphp.com/docs/5.x)
- [Spatie Laravel Permission](https://spatie.be/docs/laravel-permission/v6)
