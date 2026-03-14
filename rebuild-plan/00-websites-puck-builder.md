# Websites, Flyers & Puck Page Builder — v4 Feature Spec

## What this covers

This file specifies:
1. **Parity** with the legacy Fusion CRM website and flyer generation system (WordPress provisioning, campaign sites, PHP sites, flyer PDF generator)
2. **New**: Puck visual page builder as an additional way to create campaign sites and flyers, with live CRM data blocks (projects, lots, agents, enquiry forms)

Read this file before implementing any step that touches websites, campaign sites, flyers, or the Puck editor. Primarily applies to:
- **Step 5** (`06-step-5-tasks-relationships-marketing.md`) — data import + campaign site parity
- **Step 3** (`04-step-3-projects-and-lots.md`) — flyer parity + Puck flyer
- **Step 17** (`17-step-11-phase-2-property-builder-push-portal.md`) — WordPress parity + Puck site builder full feature

---

## 1. Legacy System Summary (what to replicate)

### 1a. WordPress Site Provisioning

**How it works in v3:**
- User fills creation form (title, contact details, colors, logos, domain)
- `ProvisionWordpressSiteJob` dispatched async
- Job POSTs to external `WP_PROVISIONER_URL` with site data + callback URL
- External provisioner creates the WP site and POSTs back to `/api/fusion/wordpress-websites/{id}` with status + credentials
- Stages: `1=Pending → 2=Initializing → 3=Active → 4=Removing`
- Cron command `ProvisionPendingWordpressSites` retries stuck sites every run (batch of 10)

**Site types:** `real_estate` | `wealth_creation` | `finance` — each has its own domain, logo requirements, IP for URL verification.

**Key fields on `wordpress_websites`:**
- `wordpress_template_id`, `title`, `url`, `type`, `stage`, `step`
- `is_custom_url`, `is_verified_url`
- `instance_id`, `url_key`, `wp_username`, `wp_password` (returned by provisioner)
- `enquiry_recipient_emails` (JSON)
- `primary_color`, `secondary_color`, `primary_text_color`
- `is_enabled` (soft on/off without reprovisioning)

**Required env vars (add to Step 0 / CHIEF pre-flight):**
```env
WP_PROVISIONER_URL=       # external WordPress provisioner service URL
PDFCROWD_USERNAME=        # legacy PDF; replace with spatie/laravel-pdf in v4
PDFCROWD_KEY=             # legacy PDF; same as above
```

**v4 implementation:**
- Keep provisioning flow (job → external URL → callback)
- Replace Restify API with standard Laravel API routes (`routes/api.php`)
- Provisioner callback: `POST /api/wordpress-websites/{id}/callback`
- Provisioner poll: `GET /api/wordpress-websites/pending` (auth: API token)
- Use Reverb to broadcast `WordpressSiteProvisioned` event → toast notification in CRM when site goes Active

### 1b. Campaign Sites (Laravel-hosted marketing pages)

**How it works in v3:**
- User creates campaign site, links to one or multiple projects
- Blade template (numbered themes: `01, 02, ...`) renders the campaign page
- Content stored as JSON blocks: `header`, `banner`, `page_content`, `footer`
- Short link via TinyURL API
- Public URL: `/campaign/{uuid}/{projectId?}`
- Survey form at `/survey/{uuid}` → creates a contact/lead

**Key fields on `campaign_websites`:**
- `site_id` (UUID for public URL)
- `short_link` (TinyURL)
- `campaign_website_template_id`
- `header`, `banner`, `page_content`, `footer` (JSON)
- `is_multiple_property` (links to multiple projects)
- `is_custom_font`, `font_link`, `font_family`
- `primary_color`, `secondary_color`
- `puck_content` (JSONB, nullable — **NEW in v4**: stores Puck editor state)
- `puck_enabled` (boolean — if true, render via Puck instead of Blade template)

**v4 parity:**
- Keep Blade template rendering for existing campaign sites (legacy data migrated)
- Add Puck editor as alternative creation mode (see §3 below)
- Public routes: `/campaign/{uuid}` → check `puck_enabled`; if true render Puck output, else render Blade template
- Survey → create contact with `contact_origin=property`, trigger `ContactCreated` Reverb event

### 1c. PHP/Laravel Websites (element-based builder)

**How it works in v3:**
- Element types: `banner`, `article`, `advertisement`, `left_image`
- Content loaded from `storage/default_data/{type}.json` with token replacement (`{image_url}`, `{comp_name}`, etc.)
- Pages per website; SEO settings per page

**v4 parity:**
- Import `websites`, `website_pages`, `website_elements` in Step 5 import command
- Keep element-based page editor in Filament (admin) or Inertia (subscriber)
- **Upgrade path**: If subscriber upgrades to Puck, migrate element content to Puck blocks (one-time conversion tool)

### 1d. Flyer Generator (PDF)

**How it works in v3:**
- Blade template per flyer theme (`resources/views/flyers/pdf_themes/{id}.blade.php`)
- Custom HTML/CSS editor (pagebuilder.blade.php) — user edits raw HTML/CSS
- PDF rendered via PdfCrowd API OR dompdf
- Ghostscript converts first PDF page to JPG thumbnail (368×232)
- Media library storage (Spatie)

**v4 flyer parity + upgrade:**
- **Replace PdfCrowd + dompdf** with **`spatie/laravel-pdf` v2** (already in plan, Step 3)
- **Replace Ghostscript thumbnail** with `spatie/laravel-pdf` first-page screenshot or a Playwright headless screenshot
- Keep existing Blade flyer templates (migrate as-is)
- **ADD**: Puck-based flyer canvas (see §3.3 below) as alternative to raw HTML editor
- Thumbnail generation: `php artisan flyer:generate-thumbnails` command (batch)

---

## 2. v4 Database Changes

### New columns on existing imported tables

```php
// campaign_websites — add to migration
$table->jsonb('puck_content')->nullable();       // Puck editor JSON state
$table->boolean('puck_enabled')->default(false); // render via Puck or Blade

// flyers — add to migration
$table->jsonb('puck_content')->nullable();       // Puck flyer JSON state
$table->boolean('puck_enabled')->default(false); // render via Puck or blade template
$table->string('thumbnail_path')->nullable();    // generated thumbnail path
```

### New tables

```php
// puck_templates — reusable Puck page templates
Schema::create('puck_templates', function (Blueprint $table) {
    $table->id();
    $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
    $table->string('name');
    $table->string('type'); // campaign_site | flyer | landing_page
    $table->jsonb('puck_content'); // full Puck editor state
    $table->string('thumbnail_path')->nullable();
    $table->boolean('is_global')->default(false); // superadmin templates visible to all
    $table->timestamps();
    $table->softDeletes();
});
```

### Required env vars

```env
WP_PROVISIONER_URL=           # external WordPress provisioner (required for WP site creation)
TINYURL_API_KEY=              # TinyURL API for campaign site short links (optional; fallback: no short link)
```

---

## 3. Puck Visual Page Builder (NEW in v4)

### What Puck is

[Puck](https://puckeditor.com/) is an open-source React visual page builder (`@measured/puck`). It stores page content as a portable JSON structure and renders it via a React renderer. It is embedded directly in the CRM as a full-screen editor page — not a separate service.

### 3.1 Installation

```bash
bun add @measured/puck
```

No backend package needed — Puck is purely a React component. The JSON output is saved to the DB (`puck_content` JSONB column).

### 3.2 Custom CRM Data Components for Puck

Register these custom Puck components — they pull live data from the CRM API and render as editable blocks in the builder:

| Component | Data source | What it renders |
|-----------|-------------|-----------------|
| `ProjectHero` | `GET /api/projects/{id}` | Hero image, title, tagline, from-price |
| `LotGrid` | `GET /api/projects/{id}/lots?status=available` | Grid of available lots with bed/bath/price |
| `LotCard` | `GET /api/lots/{id}` | Single lot detail: floor plan, price, specs |
| `AgentProfile` | `GET /api/users/{id}` (assigned agent) | Avatar, name, phone, email, CTA button |
| `EnquiryForm` | POSTs to `/api/contacts` | Creates a contact in CRM on submit; triggers `ContactCreated` |
| `ProjectGallery` | Media from project | Swiper/carousel of project images |
| `FloorPlanViewer` | Lot media | PDF/image viewer for floor plan |
| `PriceList` | Lots for project | Filterable table of all lots + prices + status dots |
| `KeyFeatures` | Editable props | Icon + text blocks (e.g. "5 min to CBD", "SMSF Eligible") |
| `TextBlock` | Editable content | Rich text, headings, paragraphs |
| `ImageBlock` | Media upload | Hero, section images |
| `VideoEmbed` | URL prop | YouTube/Vimeo embed |
| `CallToAction` | Editable props | Button with configurable link/style |
| `SurveyBlock` | POSTs to `/api/survey` | Embedded questionnaire for campaign sites |

**Data binding pattern:**
- Each component has a `projectId` or `lotId` prop set in the Puck editor
- At render time (public page), component fetches from the Fusion API
- In editor preview, data is fetched live via React Query / SWR so the editor shows real content
- AI can pre-fill component props (e.g. select the right project for a campaign site) via a Prism suggestion

### 3.3 Puck for Flyers

Puck flyer components are a **subset** of the site components, optimised for A4/Letter fixed-size canvas:

| Component | What it renders |
|-----------|-----------------|
| `FlyerHero` | Full-bleed project image + overlay title |
| `FlyerLotSpecs` | Lot specs grid (bed/bath/car/size/price) |
| `FlyerPriceTable` | 2–3 column lot comparison |
| `FlyerAgentFooter` | Agent name, phone, logo, disclaimer |
| `FlyerTextBlock` | Headline + body copy |
| `FlyerQRCode` | QR code linking to campaign site or lot page |

**Flyer PDF export flow:**
1. User finishes Puck flyer in editor → clicks "Export PDF"
2. POST `/flyers/{id}/export-pdf`
3. Backend renders Puck JSON to HTML via a Blade view that includes the Puck renderer SSR output (or uses Playwright headless to screenshot the rendered page)
4. `spatie/laravel-pdf` v2 converts to PDF → stored in media library (`flyers` collection)
5. Thumbnail: first page screenshot via Playwright or spatie/laravel-pdf screenshot → stored as `thumbnail_path`
6. Reverb broadcasts `FlyerExported` event → download link appears in CRM

### 3.4 Editor Routes (Inertia pages)

```
GET  /campaign-sites/{campaign}/edit-puck     → CampaignSiteController@editPuck
POST /campaign-sites/{campaign}/puck-save     → CampaignSiteController@savePuck
GET  /flyers/{flyer}/edit-puck                → FlyerController@editPuck
POST /flyers/{flyer}/puck-save                → FlyerController@savePuck
POST /flyers/{flyer}/export-pdf               → FlyerController@exportPdf

GET  /puck-templates                          → PuckTemplateController@index
POST /puck-templates                          → PuckTemplateController@store
GET  /puck-templates/{template}/edit          → PuckTemplateController@edit
```

**Canonical URL map additions** (add to `00-ui-design-system.md §3b`):

| URL | Controller | Notes |
|-----|-----------|-------|
| `/campaign-sites` | `CampaignSiteController@index` | List of org's campaign sites |
| `/campaign-sites/{campaign}/edit-puck` | `CampaignSiteController@editPuck` | Full-screen Puck editor |
| `/flyers/{flyer}/edit-puck` | `FlyerController@editPuck` | Full-screen Puck flyer editor |
| `/puck-templates` | `PuckTemplateController@index` | Template library |
| `/w/{uuid}` | `PublicSiteController@show` | Public campaign site (Puck or Blade) |
| `/survey/{uuid}` | `PublicSiteController@survey` | Survey form page |

### 3.5 Puck Editor UI Spec

**Mode**: Full-screen Inertia page — nav and sidebar hidden. Back arrow returns to campaign site / flyer record.

```
┌─────────────────────────────────────────────────────────────┐
│ ← Back  [Campaign Site Name]          [Preview] [Publish]  │
├──────────────┬──────────────────────────────┬───────────────┤
│  Components  │                              │  Inspector    │
│  panel       │   Canvas (live preview)      │  (selected    │
│  (left 200px)│   renders real CRM data      │  component    │
│              │                              │  props, right │
│  — Layout    │                              │  280px)       │
│  — Content   │                              │               │
│  — CRM Data  │                              │  AI suggest:  │
│  — Forms     │                              │  "Auto-fill   │
│              │                              │  from project"│
└──────────────┴──────────────────────────────┴───────────────┘
```

**"AI Suggest" button** in Inspector panel: calls Prism with component context → suggests prop values from CRM data (e.g. selects the right project, fills in agent details). Renders suggestion as a dismissible card.

**"Use Template"** button (top bar): opens template gallery modal → select a `puck_template` → loads its JSON into the editor (replacing current content with confirmation prompt).

**"Preview"** button: opens public render in new tab (unauthenticated URL).

**"Publish"** button: saves `puck_content`, sets `puck_enabled=true`, regenerates thumbnail, triggers Reverb `CampaignSitePublished` or `FlyerPublished` event.

---

## 4. WordPress Provisioner API (v4)

Replace legacy Restify endpoints with standard Laravel API routes:

```php
// routes/api.php — WordPress provisioner endpoints (secured by API token)
Route::middleware('auth:sanctum')->prefix('provisioner')->group(function () {
    Route::get('wordpress-sites/pending', [ProvisionerApiController::class, 'pending']);
    Route::get('wordpress-sites/removing', [ProvisionerApiController::class, 'removing']);
    Route::post('wordpress-sites/{site}/callback', [ProvisionerApiController::class, 'callback']);
    Route::get('subscribers/{api_key}', [ProvisionerApiController::class, 'subscriberDetail']);
});
```

**Callback payload** (provisioner → CRM):
```json
{
  "stage": 3,
  "instance_id": "wp-abc123",
  "url_key": "sitename",
  "wp_username": "admin",
  "wp_password": "generatedpass",
  "active_url": "https://sitename.domain.com"
}
```

**On callback received:**
- Update `wordpress_websites` record (stage, credentials, url)
- Broadcast `WordpressSiteProvisioned` on private user channel → toast in CRM
- Log activity via `spatie/activitylog`

**Provisioning job (v4):**
```php
// app/Jobs/ProvisionWordpressSiteJob.php
// Same flow as legacy; retries 3× with 120s backoff
// Payload includes: site data, logo URLs, template, callback URL, subscriber info
// POST to WP_PROVISIONER_URL env var
```

---

## 5. Parity Checklist (legacy → v4)

| Legacy feature | v4 equivalent | Step |
|----------------|--------------|------|
| WordPress site creation form | Inertia page / Filament form → `ProvisionWordpressSiteJob` | Step 17 |
| WordPress stages (1→2→3→4) | `spatie/laravel-model-states` on `WordpressWebsite` | Step 17 |
| WordPress provisioner API (pending/callback) | `ProvisionerApiController` on `routes/api.php` | Step 17 |
| Campaign site creation (Blade templates) | CampaignSiteController + Blade themes | Step 5 |
| Campaign public URL + survey | `PublicSiteController` routes `/w/{uuid}` + `/survey/{uuid}` | Step 5 |
| TinyURL short link | `TinyUrlService` via Saloon or `Http::get()` (same as legacy) | Step 5 |
| PHP website element builder | Inertia page or Filament resource | Step 5 |
| Flyer HTML/CSS editor | Keep as fallback; Puck editor as primary | Step 3 |
| Flyer PDF export | `spatie/laravel-pdf` v2 (replaces PdfCrowd/dompdf) | Step 3 |
| Flyer thumbnail | Playwright screenshot OR spatie/laravel-pdf screenshot | Step 3 |
| **NEW: Puck campaign site editor** | `@measured/puck` + CRM data components | Step 17 |
| **NEW: Puck flyer editor** | Puck fixed-canvas + PDF export | Step 3 (basic) / Step 17 (full) |
| **NEW: Puck template library** | `puck_templates` table + template gallery UI | Step 17 |
| **NEW: AI prop suggestions in Puck** | Inspector "AI Suggest" → Prism → fill props | Step 17 |

---

## 6. Required Step File Updates

After reading this file, update the following step files to reference it:

### Step 3 (`04-step-3-projects-and-lots.md`) — add to Deliverables:
- Flyer parity: `spatie/laravel-pdf` v2 for PDF export; Blade templates migrated
- Basic Puck flyer canvas (FlyerHero, FlyerLotSpecs, FlyerAgentFooter components)
- Flyer thumbnail generation command

### Step 5 (`06-step-5-tasks-relationships-marketing.md`) — add to Deliverables:
- Campaign site parity (Blade templates + JSON content)
- Public routes `/w/{uuid}` and `/survey/{uuid}`
- TinyURL short link service
- PHP website element builder parity
- Import `puck_content` and `puck_enabled` columns (nullable/false by default)

### Step 17 (`17-step-11-phase-2-property-builder-push-portal.md`) — add to Deliverables:
- WordPress site provisioning parity (full flow: form → job → provisioner → callback → Reverb notification)
- WordPress provisioner API (`ProvisionerApiController`)
- Puck editor full feature (all CRM data components, template library, AI suggest)
- Puck campaign site editor + publish flow
- Puck flyer editor (full component set + PDF export)
- `puck_templates` table + template gallery

---

## 7. Env Vars Summary (add to CHIEF.md pre-flight)

| Variable | Required? | Used by |
|----------|-----------|---------|
| `WP_PROVISIONER_URL` | Required for WP sites | `ProvisionWordpressSiteJob` |
| `TINYURL_API_KEY` | Optional | Campaign site short links; skip if blank |

---

## 8. AI Integration Points

| Feature | AI tool | Where |
|---------|---------|-------|
| Puck component prop auto-fill | Prism (OpenRouter) | Inspector "AI Suggest" button |
| Campaign site copy generation | Prism | "Generate copy" button in TextBlock component |
| Flyer headline suggestion | Prism | FlyerHero component inspector |
| Lot description for PriceList | Prism | Auto-generates short description per lot |
| Template suggestion | `DashboardInsightAgent` (Laravel AI SDK) | "Which template fits this project?" |
| Buyer-to-campaign-site match | PropertyAgent tool | "Which campaign sites match this contact's profile?" |

---

## 9. Visual QA Checks (add to `00-visual-qa-protocol.md`)

After Task 12 (Step 17), verify:
- [ ] `/campaign-sites` lists existing campaign sites with thumbnail previews
- [ ] "Edit with Puck" button opens full-screen editor (nav hidden, Puck panels visible)
- [ ] CRM data components (ProjectHero, LotGrid) render real data in editor canvas (not placeholder)
- [ ] "Publish" saves and redirects to public URL `/w/{uuid}`
- [ ] Public campaign site renders correctly (unauthenticated)
- [ ] Survey form on `/survey/{uuid}` creates a contact in CRM
- [ ] WordPress site creation form submits and shows "Pending" stage
- [ ] WordPress site transitions to "Active" on provisioner callback (can simulate with test callback)
- [ ] Flyer Puck editor opens with A4 canvas
- [ ] "Export PDF" generates a downloadable PDF with real lot/project data

