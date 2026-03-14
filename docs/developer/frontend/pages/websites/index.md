# websites/index

## Purpose

Displays the five website slots (2 PHP, 3 WordPress) for the current organization. Each slot shows the existing site's status badge and domain if provisioned, or a "Create" CTA otherwise. Supports creating new websites and removing existing ones.

## Location

`resources/js/pages/websites/index.tsx`

## Route

`GET /website-index` → `website-index.index` (auth, verified, tenant)

## Props

| Prop       | Type                              | Description                                  |
|------------|-----------------------------------|----------------------------------------------|
| `websites` | `Record<string, Website[]>`       | Sites grouped by `site_type`                 |

## Components Used

- `AppLayout` — standard authenticated layout
- `Card`, `Badge`, `Button`, `Dialog`, `Input`, `Label` — shadcn/ui components
- `CreateWebsiteDialog` — inline dialog for creating a new site in a slot

## Site Slots

| Key                 | Label               |
|---------------------|---------------------|
| `php_standard`      | PHP Standard        |
| `php_premium`       | PHP Premium         |
| `wp_real_estate`    | WP Real Estate      |
| `wp_wealth_creation`| WP Wealth Creation  |
| `wp_finance`        | WP Finance          |

## Stage Badges

| Stage | Label        |
|-------|--------------|
| 1     | Pending      |
| 2     | Provisioning |
| 3     | Active       |
| 4     | Removing     |

## Related

- **Controller**: `WebsiteController`
- **Model**: `WordpressWebsite`
- **Routes**: `website-index.store`, `website-index.destroy`
