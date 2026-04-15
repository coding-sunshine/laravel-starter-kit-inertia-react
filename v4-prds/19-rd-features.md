# PRD 19: R&D & Special Features

> **Phase 3** — R&D features ship after subscriber migration and core platform stabilization.

## Overview

Deliver AI-generated suburb and state data for projects, AI extraction of photos/floor plans from brochures, email-to-builders templates, Resemble.ai voice cloning for personalised AI agents, and placeholder for future R&D suggestions. This is the final Phase 2 step. The spec is exploratory in nature — stories focus on core deliverables with clear boundaries.

**Prerequisites:** PRDs 00-18 complete (signup working, all Phase 2 features delivered).

## Technical Context

- **AI:** `laravel/ai` + `prism-php/prism` for structured output (suburb data) and vision API (brochure extraction)
- **Voice:** Resemble.ai API via `saloonphp/saloon` for voice cloning
- **Email:** Laravel Mail with templates for builder communication; `backstage/laravel-mails` for tracking
- **Media:** `spatie/laravel-media-library` for extracted images
- **Module:** `modules/module-crm/`, namespace `Cogneiss\ModuleCrm`
- **Env:** `RESEMBLE_API_KEY`, `RESEMBLE_PROJECT_UUID`

### Required Environment Variables
Before starting this PRD, verify these are set in `.env`:
- `DB_CONNECTION=pgsql` — PostgreSQL connection (set in preflight)

No special API keys required for this PRD (Resemble.ai keys are optional R&D features).

**If any required key is missing, ASK the user before proceeding. Do not skip, stub, or use placeholder values.**

## User Stories

### US-001: AI Suburb & State Data

**Status:** todo
**Priority:** 1
**Description:** Fetch or generate suburb-level price and rent data for projects.

- [ ] Create migration `create_suburb_data_table`: id, suburb (string), state (string), postcode (string nullable), source (string: rea/ai/manual), median_price (decimal 12,2 nullable), median_rent_weekly (decimal 8,2 nullable), growth_rate_annual (decimal 5,2 nullable), data_json (jsonb — full payload), fetched_at (timestamp), created_at, updated_at. Unique(suburb, state)
- [ ] Create `Cogneiss\ModuleCrm\Models\SuburbData`
- [ ] Create `FetchSuburbDataAction`: attempts external API (REA data feed or similar) first; falls back to `laravel/ai` to generate estimated data from training knowledge
- [ ] Show suburb data card on project show page: median price, median rent, growth rate
- [ ] Artisan command `crm:fetch-suburb-data` to bulk-fetch for all project suburbs
- [ ] Cache suburb data for 30 days before re-fetching
- [ ] Verify: project show page displays suburb median price and rent data

### US-002: AI Brochure Extraction

**Status:** todo
**Priority:** 2
**Description:** Upload a brochure PDF/image and extract facade photos, floor plans via AI vision.

- [ ] Create `ExtractBrochureContentAction`: accepts uploaded file (PDF or image), sends to `laravel/ai` with vision capability
- [ ] AI extracts: facade photo description, floor plan dimensions, key features, pricing if visible
- [ ] Extracted images (if PDF pages are split): save to project/lot media collection via Spatie Media Library
- [ ] Extracted text data: store in project `extra_attributes` under `brochure_extracted` key
- [ ] UI: "Upload Brochure" button on project show page → processing indicator → extracted content preview → confirm to save
- [ ] Verify: uploading a brochure PDF extracts at least 1 image and text description to the project

### US-003: Email to Builders — Templates

**Status:** todo
**Priority:** 2
**Description:** CRM action to send templated emails to builder contacts.

- [ ] Create 4 email templates as database-mail events:
  - `BuilderPriceListRequestEvent` — "Request Price List & Availability"
  - `BuilderMoreInfoRequestEvent` — "Request More Information"
  - `BuilderHoldRequestEvent` — "Request to Hold a Lot"
  - `BuilderPropertyRequestEvent` — "Property Request for Client"
- [ ] Register all 4 in `config/email-templates.php` via `CrmModuleServiceProvider::boot()` with default template content
- [ ] Org admin can customize template content via starter kit's existing Inertia page at `/settings/email-templates`
- [ ] Templates use variables: {{ builder.name }}, {{ project.title }}, {{ lot.number }}, {{ agent.name }}, {{ client.name }}
- [ ] CRM action on project/lot show page: "Email Builder" dropdown → select template → preview → send
- [ ] Track sent emails via `backstage/laravel-mails` and show in activity log
- [ ] Verify: sending "Request Price List" email to builder from project page delivers email with correct template data

### US-004: Resemble.ai Voice Cloning Integration

**Status:** todo
**Priority:** 3
**Description:** Integrate Resemble.ai API for custom voice agent creation.

- [ ] Create `ResembleConnector` in `app/Http/Integrations/Resemble/` using Saloon
- [ ] Create actions: `CreateVoiceAction` (upload audio samples, get voice UUID), `GenerateSpeechAction` (text → audio using cloned voice), `GetVoiceAction` (fetch voice details)
- [ ] Store `resemble_voice_uuid` on users via schemaless `extra_attributes`
- [ ] Admin page: upload voice samples for a user → create cloned voice → store UUID
- [ ] Integration points (for future wiring):
  - Bot In A Box v2 (PRD 11) TTS responses can use cloned voice
  - Vapi.ai flows (PRD 11) can use as custom voice provider
  - Cold outreach (PRD 10) can generate personalised voice messages
- [ ] Fallback: if no cloned voice, use Vapi default TTS voice
- [ ] Env: `RESEMBLE_API_KEY`, `RESEMBLE_PROJECT_UUID`
- [ ] Verify: creating a voice via Resemble API returns UUID; generating speech returns audio file (or note if API key unavailable — defer to post-launch)

### US-005: R&D Suggestions Placeholder

**Status:** todo
**Priority:** 5
**Description:** Document and stub integration points for future R&D features.

- [ ] Create `/crm/settings/rd-features` page showing list of experimental features with descriptions
- [ ] Each feature: name, description, status (available/coming-soon/experimental), toggle (feature flag)
- [ ] Feature flags for R&D: `ResembleVoiceFeature`, `BrochureExtractionFeature`, `SuburbDataFeature`
- [ ] Document expected API integration points for Joey's/Geanelle's suggestions when available
- [ ] Verify: R&D features page loads showing feature list with toggle controls
