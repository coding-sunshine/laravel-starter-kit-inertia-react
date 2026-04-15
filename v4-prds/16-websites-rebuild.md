# PRD 16: Websites & API Rebuild (From Scratch)

> **Phase 2** — Runs after subscriber migration. Note: Builder Portal API (PRD 12 US-010) may ship earlier in Phase 1.

## Overview

Rebuild the Websites and API layer from scratch (NOT migrating v3 WordPress logic directly). Includes Fusion API v2 (REST + GraphQL), Zapier/Make webhook integration, open API documentation via Scramble, WordPress site provisioning (ported from v3 logic), campaign website editor (form-based, NOT Puck), and site type management. Builds on Phase 1 + PRDs 09-15.

**Prerequisites:** PRDs 00-15 complete (marketing, flyers, email campaigns working).

## Technical Context

- **API:** `laravel/sanctum` for token auth, `spatie/laravel-query-builder` for filters, `essa/api-tool-kit` for CRUD helpers
- **API Docs:** `dedoc/scramble` for OpenAPI auto-generation
- **Webhooks:** `spatie/laravel-webhook-server` for outbound webhooks to Zapier/Make
- **WP Provisioning:** Port from v3 `ProvisionWordpressSiteJob` — external provisioner creates WP instances
- **Campaign Editor:** Form-based with CKEditor (NOT Puck)
- **TinyURL:** For short link generation on campaign pages
- **Module:** `modules/module-crm/`, namespace `Cogneiss\ModuleCrm`
- **Env:** `WP_DOMAIN_*`, `WP_IP_*`, `WP_PROVISIONER_URL`, `TINYURL_API_KEY`

### Required Environment Variables
Before starting this PRD, verify these are set in `.env`:
- `DB_CONNECTION=pgsql` — PostgreSQL connection (set in preflight)

No special API keys required for this PRD (WP provisioner and TinyURL keys are optional and can be added when ready).

**If any required key is missing, ASK the user before proceeding. Do not skip, stub, or use placeholder values.**

## User Stories

### US-001: Fusion API v2 — REST Endpoints

**Status:** todo
**Priority:** 1
**Description:** Build versioned REST API for contacts, projects, lots, sales, tasks, and reservations.

- [ ] Create API routes in `routes/api.php` under `v2` prefix: `/api/v2/contacts`, `/api/v2/projects`, `/api/v2/lots`, `/api/v2/sales`, `/api/v2/tasks`, `/api/v2/reservations`
- [ ] Each endpoint: index (paginated, filterable via `spatie/laravel-query-builder`), show, store, update, soft-delete
- [ ] Auth: Sanctum personal access tokens, scoped to organization
- [ ] Rate limiting: 60 requests/minute per token (configurable)
- [ ] API Resources (JsonResource) for consistent response format
- [ ] Include relationships: `?include=emails,phones,tags` on contacts; `?include=lots` on projects
- [ ] Verify: `GET /api/v2/contacts?filter[stage]=qualified&sort=-created_at` returns paginated, filtered results with 200

### US-002: Fusion API v2 — GraphQL (Optional)

**Status:** todo
**Priority:** 3
**Description:** Add GraphQL endpoint for flexible data querying.

- [ ] Install and configure GraphQL package (e.g., `nuwave/lighthouse` or `rebing/graphql-laravel`)
- [ ] Define types: Contact, Project, Lot, Sale, Task, Reservation with relationships
- [ ] Queries: contacts, projects, lots, sales with pagination and filters
- [ ] Mutations: createContact, updateContact, createTask
- [ ] Auth: Sanctum token via Bearer header
- [ ] Org-scoped: all queries filtered by authenticated user's organization
- [ ] Verify: GraphQL query `{ contacts(stage: "qualified") { id, first_name, email } }` returns correct data

### US-003: Webhook Integration for Zapier/Make

**Status:** todo
**Priority:** 2
**Description:** Register CRM events with the starter kit's existing outbound webhook infrastructure.

> **Starter kit provides:** `WebhookEndpoint` model, `WebhookDispatcher` service, `WebhooksController`, Inertia CRUD at `/settings/webhooks` (with test button, circuit breaker, secret regeneration). All org-scoped.

CRM-specific work:
- [ ] Register CRM webhook events in `config/webhooks.php` via `CrmModuleServiceProvider::boot()`: `contact.created`, `contact.updated`, `sale.created`, `sale.stage_changed`, `task.completed`, `reservation.created`
- [ ] Add `WebhookDispatcher::dispatch()` calls in CRM model observers (Contact, Sale, Task, Reservation)
- [ ] Verify: registering a webhook for contact.created and creating a contact triggers POST to the URL

### US-004: API Documentation via Scramble

**Status:** todo
**Priority:** 2
**Description:** Auto-generate and publish OpenAPI documentation.

- [ ] Configure `dedoc/scramble` in `config/scramble.php` for v2 API routes
- [ ] Add proper PHPDoc annotations to all API controllers (param types, response types)
- [ ] Run `php artisan generate:api-documentation` to generate OpenAPI spec
- [ ] Publish docs at `/api/docs` (Scramble's built-in UI)
- [ ] Include authentication instructions, rate limiting info, and example requests
- [ ] Verify: `/api/docs` loads and shows all v2 endpoints with correct request/response schemas

### US-005: WordPress Site Provisioning (Port from v3)

**Status:** todo
**Priority:** 2
**Description:** Port the WordPress site provisioning flow exactly from v3. See `archive/rebuild-plan/00-websites-puck-builder.md` for full spec.

**WordPress Provisioner API (v4):** Replace Restify with standard Laravel API routes:
```php
// routes/api.php — secured by Sanctum
Route::middleware('auth:sanctum')->prefix('provisioner')->group(function () {
    Route::get('wordpress-sites/pending', [ProvisionerApiController::class, 'pending']);
    Route::get('wordpress-sites/removing', [ProvisionerApiController::class, 'removing']);
    Route::post('wordpress-sites/{site}/callback', [ProvisionerApiController::class, 'callback']);
});
```
On callback: update site record (stage, credentials, URL), broadcast `WordpressSiteProvisioned` via Reverb, log via activitylog.

- [ ] Create `ProvisionWordpressSiteJob` in `app/Jobs/`: port logic from v3 exactly — $tries=3, $backoff=120, stage transitions 1→2→3
- [ ] If `WP_PROVISIONER_URL` empty: log warning, return (no crash)
- [ ] POST to provisioner: full site record + logo_urls (Spatie Media) + fusion_update_url + fusion_site_id
- [ ] On HTTP error: revert to stage=1, throw for retry
- [ ] Create `PATCH /api/websites/{id}` endpoint for provisioner callback: accepts stage, instance_id, url_key, url, wp_username, wp_password
- [ ] Create `wp:provision-pending` artisan command: queries websites where stage IN [1,2], dispatches job for each, limit flag
- [ ] Register in scheduler: `$schedule->command('wp:provision-pending')->everyMinute()->withoutOverlapping()`
- [ ] Verify: creating a WP site record dispatches provisioning job; callback endpoint updates site to stage 3

### US-006: Website Site Types & Dashboard

**Status:** todo
**Priority:** 2
**Description:** Manage 5 site types (2 PHP + 3 WP) per subscriber.

- [ ] Add `site_type` enum column to `websites` table: php_standard, php_premium, wp_real_estate, wp_wealth_creation, wp_finance
- [ ] Unique constraint: `UNIQUE(user_id, site_type)` — max 1 of each type
- [ ] Website dashboard at `/crm/websites` showing 5 site slots per subscriber
- [ ] Each slot: status badge (Active/Provisioning/Not Created), domain link, edit button, "Create" CTA
- [ ] Feature flags: `WordPressSitesFeature` gates WP sites, `PhpSitesFeature` gates PHP sites
- [ ] Plan limits: `max_php_sites` and `max_wp_sites` from plan features JSON
- [ ] Verify: subscriber on Growth plan sees 2 PHP + 3 WP slots; Starter plan sees 1 PHP only

### US-007: Campaign Website Editor (Form-Based + Puck)

**Status:** todo
**Priority:** 3
**Description:** Form-based campaign website editor with CKEditor as the primary mode. Puck visual page builder (`@measured/puck`) as an alternative creation mode. See `archive/rebuild-plan/00-websites-puck-builder.md` for full Puck spec.

**Puck Integration:**
- Install: `bun add @measured/puck`
- New columns: `campaign_websites.puck_content` (JSONB nullable), `campaign_websites.puck_enabled` (boolean default false)
- New table: `puck_templates` (id, organization_id, name, type, puck_content JSONB, thumbnail_path, is_global, timestamps, softDeletes)
- Public URL: `/w/{uuid}` — check `puck_enabled`: if true render Puck output, else render Blade template
- Editor routes: `GET /campaign-sites/{campaign}/edit-puck`, `POST /campaign-sites/{campaign}/puck-save`
- Template routes: `GET /puck-templates`, `POST /puck-templates`

**Custom CRM Data Components for Puck:**
ProjectHero, LotGrid, LotCard, AgentProfile, EnquiryForm (POSTs to /api/contacts), ProjectGallery, FloorPlanViewer, PriceList, KeyFeatures, TextBlock, ImageBlock, VideoEmbed, CallToAction, SurveyBlock

**Puck for Flyers (fixed A4 canvas):**
FlyerHero, FlyerLotSpecs, FlyerPriceTable, FlyerAgentFooter, FlyerTextBlock, FlyerQRCode
- PDF export: `POST /flyers/{flyer}/export-pdf` -> starter kit's `GeneratePdfJob` -> stored in media library
- Thumbnail: first-page screenshot via Playwright or `GeneratePdf` action

**"AI Suggest" button** in Puck Inspector: calls Prism with component context, suggests prop values from CRM data.
**"Use Template" button**: opens gallery modal, loads puck_template JSON into editor.

- [ ] Create/edit page for campaign_websites using Inertia form
- [ ] Sections: header copy (CKEditor), body copy (CKEditor), footer copy (CKEditor)
- [ ] Color pickers: background, text, CTA button colors
- [ ] Image uploads: hero image, logo (Spatie Media Library)
- [ ] Multi-project selector: associate projects with campaign page (campaign_website_project pivot)
- [ ] Form settings: enable/disable enquiry form, toggle visible fields
- [ ] TinyURL integration: on publish, POST to TinyURL API to generate short URL, store in `campaign_websites.short_url`
- [ ] Add `@ckeditor/ckeditor5-react` and `@ckeditor/ckeditor5-build-classic` via bun if not present
- [ ] Feature-flagged behind `CampaignWebsitesFeature`
- [ ] Verify: editing campaign website with CKEditor saves content; publishing generates TinyURL

### US-008: Login Events for Same Device Detection

**Status:** todo
**Priority:** 4
**Description:** Track login events for security reporting.

- [ ] Create migration `create_login_events_table`: id, user_id (FK users CASCADE), ip_address (string), user_agent (text), device_fingerprint (string nullable), created_at
- [ ] Record login event on successful authentication (via LoginEvent listener)
- [ ] Add `@fingerprintjs/fingerprintjs` via bun for frontend fingerprinting
- [ ] Report page showing logins by device/IP for security review
- [ ] Verify: logging in creates a login_events record with IP and user agent
