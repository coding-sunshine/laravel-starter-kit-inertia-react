# appearance/update

## Purpose

Appearance settings: theme/preferences via `AppearanceTabs`. Rendered by route closure.

## Location

`resources/js/pages/appearance/update.tsx`

## Route Information

- **URL**: `settings/appearance`
- **Route Name**: `appearance.edit`
- **HTTP Method**: GET
- **Middleware**: `web`, `auth`

## Props (from Controller)

None (route closure).

## User Flow

1. User visits `settings/appearance`.
2. Adjusts appearance options (e.g. theme) in tabs.
3. Changes applied via `AppearanceTabs` logic.

## Related Components

- **Route**: `appearance.edit` (closure in `routes/web.php`)
- **Layout**: `AppLayout`, `SettingsLayout`
- **Component**: `AppearanceTabs`

## Implementation Details

Uses `AppLayout`, `SettingsLayout`, `AppearanceTabs`. Breadcrumbs to appearance settings.
