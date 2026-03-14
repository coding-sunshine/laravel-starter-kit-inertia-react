# WebsiteController

## Purpose

Manages WordPress/PHP website slots per organization. Renders the websites Inertia page, handles provisioning via `ProvisionWordpressSiteJob`, and receives provisioner callbacks to update site status.

## Location

`app/Http/Controllers/WebsiteController.php`

## Methods

| Method                | HTTP        | Route / name                | Purpose                                          |
|-----------------------|-------------|------------------------------|--------------------------------------------------|
| `index`               | GET         | `website-index`              | Show websites Inertia page grouped by site_type  |
| `store`               | POST        | `website-index`              | Create website + dispatch provisioning job       |
| `destroy`             | DELETE      | `website-index/{website}`    | Soft-delete a website                            |
| `provisionerCallback` | PATCH       | `api/websites/{id}`          | Server-to-server callback from provisioner       |

## Routes

- `website-index.index`: GET `website-index` (middleware: auth, verified, tenant)
- `website-index.store`: POST `website-index` (middleware: auth, verified, tenant)
- `website-index.destroy`: DELETE `website-index/{website}` (middleware: auth, verified, tenant)
- `api.websites.callback`: PATCH `api/websites/{id}` (no user auth — server-to-server)

## Site Types

| Key               | Description               |
|-------------------|---------------------------|
| `wp_real_estate`  | WordPress real estate portal |
| `wp_wealth_creation` | WordPress wealth creation |
| `wp_finance`      | WordPress finance portal  |
| `php_standard`    | Standard PHP website      |
| `php_premium`     | Premium PHP website       |

## Related

- **Model**: `WordpressWebsite`
- **Job**: `ProvisionWordpressSiteJob`
- **Command**: `wp:provision-pending`
- **Page**: `resources/js/pages/websites/index.tsx`
- **Existing provisioner**: `Api\ProvisionerApiController`
