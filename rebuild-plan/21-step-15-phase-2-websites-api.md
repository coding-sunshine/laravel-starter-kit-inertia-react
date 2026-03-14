# Step 21 (Step 15): Phase 2 — Websites & API

## Goal

Add **Fusion API v2** (REST + GraphQL, open developer access), **Zapier & Make integration** (trigger-based workflows), and **open API documentation**. Builds on Steps 0–20. WordPress Site Hub and PHP Fast Site Engine referenced; API serves both internal and external consumers.

## Starter Kit References

- **Laravel Sanctum**: API tokens
- **Scramble / OpenAPI**: If kit has API docs
- **Routes**: `routes/api.php`; versioned API prefix

## Deliverables

1. **Fusion API v2**: REST and GraphQL endpoints for contacts, projects, lots, sales, tasks, reservations (read and where appropriate write); pagination, filters; token auth (Sanctum) or OAuth2; rate limiting. Use **spatie/laravel-query-builder** for filter/sort; **essa/api-tool-kit** for CRUD helpers. See 00-kit-package-alignment.md.
2. **Zapier & Make integration**: Webhooks for events (e.g. new lead, new sale); triggers and actions for Zapier/Make; document endpoints and payloads.
3. **Open API documentation**: Dev-accessible, structured REST/GraphQL docs (e.g. OpenAPI 3, GraphQL schema); publish to /api/docs or developer portal. Run **php artisan generate:api-documentation** (Scramble) to auto-generate API docs from routes; make this an acceptance criterion. See 00-kit-package-alignment.md.

## WordPress Site Provisioning — Port from v3 (Same Logic, Same Flow)

> **Source:** `app/Jobs/ProvisionWordpressSiteJob.php`, `app/Console/Commands/ProvisionPendingWordpressSites.php`, `app/Console/Kernel.php` in legacy v3. Port this logic exactly — do NOT redesign it.

### How it works

1. User creates a WordPress site → record saved in `websites` with `stage=1` (pending)
2. `ProvisionWordpressSiteJob` dispatched immediately
3. Job sets `stage=2` (initializing), then POSTs to `WP_PROVISIONER_URL` if set
4. External provisioner spins up WordPress, then PATCHes back to Fusion to set `stage=3` + credentials
5. Scheduler runs `wp:provision-pending` every minute to retry any stuck stage 1/2 sites

### Env vars required (add to `.env` and Step 0 checklist)

```env
# WP server targets (required for provisioner to know where to deploy)
WP_DOMAIN_REAL_ESTATE=mypropertyenquiry.com.au
WP_DOMAIN_WEALTH_CREATION=mypropertyenquiry.com.au
WP_DOMAIN_FINANCE=mypropertyenquiry.com.au

WP_IP_REAL_ESTATE=54.253.51.191
WP_IP_WEALTH_CREATION=54.253.51.191
WP_IP_FINANCE=54.253.51.191

# Optional: URL of the external provisioner service.
# If set: Fusion POSTs site payload here; provisioner creates WP instance and PATCHes back.
# If NOT set: site stays at stage=2 (initializing); no crash. Manual provisioning or polling required.
WP_PROVISIONER_URL=
```

### Job — `App\Jobs\ProvisionWordpressSiteJob`

Port exactly from v3:
- `$tries = 3`, `$backoff = 120` (retry 3× with 2-min backoff)
- Skip if `stage === 3` (active) or `stage === 4` (removing)
- Move `stage 1 → 2` on first run
- If `WP_PROVISIONER_URL` empty: log warning, return (do not fail)
- POST to provisioner: full site record array + `logo_urls` + `fusion_update_url` + `fusion_site_id`
  - `fusion_update_url` = `APP_URL + /api/websites/{id}` (v4 REST endpoint)
- On HTTP error: revert to `stage=1`, throw to trigger retry
- On success: log info

```php
// Payload shape sent to provisioner:
[
    // Full website record (id, title, url, site_type, wordpress_template_id, colors, etc.)
    'logo_urls'         => [...],   // from Spatie Media Library
    'fusion_update_url' => 'https://app.fusioncrm.software/api/websites/{id}',
    'fusion_site_id'    => 227,
]
```

### Provisioner callback — PATCH `/api/websites/{id}`

The external provisioner PATCHes this endpoint when provisioning is complete:

```json
{
    "stage": 3,
    "instance_id": "wp-12345",
    "url_key": "subdomain-or-path",
    "url": "https://final-site-url.com",
    "wp_username": "admin",
    "wp_password": "generated-password"
}
```

Implement `PATCH /api/websites/{id}` in `routes/api.php` → `WebsiteController@provisionerCallback`. No auth required on this endpoint (provisioner is trusted server-to-server).

### Artisan command — `wp:provision-pending`

Port exactly from v3:
- Signature: `wp:provision-pending {--limit=10}`
- Query: `websites` where `stage IN [1, 2]`, ordered by `created_at`, limited by `--limit`
- Dispatch `ProvisionWordpressSiteJob` for each
- Registered in scheduler: **every minute**, `withoutOverlapping()`

```php
// routes/console.php or app/Console/Kernel.php
$schedule->command('wp:provision-pending')->everyMinute()->withoutOverlapping();
```

---

## WordPress Site Types — 3 Distinct Types (Confirmed from Live v3)

> **Source**: Live site — subscribers can have up to 3 WordPress sites, each a different type: `real_estate`, `wealth_creation`, `finance`.

### `websites` table — `site_type` column

Add `site_type` enum column to `websites` table:
```
site_type ENUM('php_standard', 'php_premium', 'wp_real_estate', 'wp_wealth_creation', 'wp_finance')
```

**Unique constraint**: `UNIQUE(user_id, site_type)` — subscribers can have at most 1 of each type.

**WP provisioner payload**: Include `site_type` in the provisioner API call so the WP provisioner can apply the correct WordPress theme + plugin template per type:
- `wp_real_estate` → real estate theme + property listing plugins
- `wp_wealth_creation` → finance/investment theme + lead capture plugins
- `wp_finance` → finance/broker theme + compliance plugins

**Dashboard `/website-index`**: Shows 5 site slots per subscriber:
- 2 PHP site slots (php_standard, php_premium)
- 3 WP site slots (wp_real_estate, wp_wealth_creation, wp_finance)
- Each slot: status badge (Active / Provisioning / Not Created), domain link, edit button, or "Create" CTA if not yet provisioned

**Feature flag**: Gate WP sites behind `WordPressSitesFeature` (Step 0 Pennant feature class). Gate PHP sites behind `PhpSitesFeature`.

---

## Landing Page Editor — Form-Based (NOT Puck) — Clarification

> **Source**: Live audit of `/campaign_website/198/edit` — the editor is a **structured form**, NOT a visual drag-drop builder.

### What the editor IS

A **multi-section form-based editor** with:
- **CKEditor** rich-text sections: header copy, body copy, footer copy
- Color picker inputs: background color, text color, CTA button color
- Image upload fields: hero image, logo (Spatie Media Library)
- Multi-project selector: associate projects with this campaign page (campaign_website_project pivot)
- Form settings: enable/disable enquiry form, toggle which fields show
- **TinyURL** short link generation (uses `TINYURL_API_KEY` env var — add to Step 0 env checklist)

### What the editor is NOT

Do **NOT** use Puck (`@measured/puck`) for landing pages. Puck is a visual block builder; landing pages are structured content, not free-form layouts.

### Implementation

**CKEditor**: `@ckeditor/ckeditor5-react` — verify presence in starter kit's `package.json`; if absent, add via bun:
```bash
bun add @ckeditor/ckeditor5-react @ckeditor/ckeditor5-build-classic
```

**TinyURL integration**: Add `TINYURL_API_KEY` to `.env`. On campaign website save (if `is_published = true`), POST to TinyURL API to generate/update the short URL; store in `campaign_websites.short_url`.

**Flyer editor** (separate — NOT landing pages): The `FlyrEditor` React component uses **Puck v0.20.2** (already in starter kit `package.json`). This is correct — flyers are free-form designed PDFs. Landing pages are structured content forms.

---

## DB Design (this step)

- Optional **api_logs** or use activity_log; **webhook_endpoints** (org_id, url, events, secret); **api_keys** or use Sanctum personal access tokens.
- **`websites.site_type`** enum column (add in this step or backfill from Step 5 where `websites` table is created).
- **`login_events`** table (for Same Device Detection report from Step 7): `id, user_id, ip_address, user_agent, device_fingerprint, created_at`. Add `@fingerprintjs/fingerprintjs` via bun for frontend fingerprinting.

## Data Import

- **None.**

## AI Enhancements

- None required.

## Verification (verifiable results)

- Call REST and GraphQL endpoints with token; trigger webhook and see event in Zapier/Make; view API docs.

## Human-in-the-loop (end of step)

**STOP after this step. Do not proceed to Step 22 until the human has completed the checklist below.**

Human must:
- [ ] Confirm REST and GraphQL endpoints respond correctly with auth.
- [ ] Confirm webhook triggers and Zapier/Make (or test endpoint) receive events.
- [ ] Confirm API documentation is accessible and accurate (including Scramble-generated docs via `generate:api-documentation`).
- [ ] Approve proceeding to Step 22 (Phase 2: Xero integration).

## Acceptance Criteria

- [ ] Fusion API v2 (REST + GraphQL), Zapier/Make integration, and open API documentation delivered per scope.
- [ ] **php artisan generate:api-documentation** (Scramble) run and API docs published/accessible.
