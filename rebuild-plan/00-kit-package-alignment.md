# Kit Package Alignment — CRM Plan

This document maps **starter kit packages** to rebuild steps so implementation uses the kit’s conventions and an AI (or developer) does not invent alternatives. Read this together with **00-database-design.md** and the step files.

---

## 🔴 P0 — Critical (must be in plan)

### 1. Legacy ID columns (sync & rollback)

- **contacts.legacy_lead_id** (bigint, nullable, indexed) — stores MySQL `leads.id`. See 00-database-design §2.2 and Step 1 migration.
- **projects.legacy_id**, **lots.legacy_id**, **sales.legacy_id** (bigint, nullable, indexed) — store original MySQL ids for Step 25 two-way sync and debugging. Add in Steps 3 and 4 migrations.

### 2. State machines — spatie/laravel-model-states + a909m/filament-statefusion

Do **not** use raw string enums only; use the kit’s state machine packages for consistent transitions and Filament UI.

- **Step 1 — Contact:** Contact model uses **spatie/laravel-model-states** for `stage`. States (example): New → Qualified → Proposal → Converted → Archived. Use **filament-statefusion** for transition UI in the Contact Filament resource.
- **Step 4 — Sale & PropertyReservation:** Sale uses state machine for status; PropertyReservation uses it for reservation status. Use filament-statefusion in Filament resources for status transitions.

### 3. Laravel Pennant — CRM Feature classes

Step 23 “feature flags” must use the kit’s **App\Features\*** classes and **config/feature-flags.php**. In **Step 0** (or before Step 23), create and register:

- **AiToolsFeature** — AI agents, prompt commands  
- **PropertyAccessFeature** — property portal (projects, lots)  
- **CampaignWebsitesFeature** — campaign website builder  
- **WordPressSitesFeature** — WordPress integration  
- **PhpSitesFeature** — PHP site management  
- **EwayPaymentsFeature** — reservation eWAY payment (Step 4)

Register in `config/feature-flags.php` (inertia_features + route_feature_map). In **Step 23** map plan tiers to these features via Pennant segments.

### 4. martinpetricko/laravel-database-mail — Email triggers

Do **not** use raw `Mail::to()->send()` for CRM emails. Use the kit’s **laravel-database-mail** (event-driven templates; `config/database-mail.php` events).

- **Step 4:** Create **ReservationCreatedEvent** → confirmation email to primary contact. Register in config.
- **Step 5:** Create **TaskAssignedEvent** (task due reminder). Register in config.
- **Step 7:** Create **CommissionUpdatedEvent**. Register in config.
- **Step 23:** Create **SubscriberSignedUpEvent** (welcome), **OnboardingReminderEvent**. Register in config.

### 5. laravel/mcp — CRM MCP tools (Step 6)

Kit has MCP server at `/mcp/api` and **App\Mcp\Servers\ApiServer**. In **Step 6** register CRM tools in `ApiServer::$tools` (or equivalent):

- **contacts_search** — filter by name, email, stage, origin  
- **contacts_show** — by id  
- **projects_search** — filter by stage, suburb, developer  
- **lots_filter** — by project, stage, price range  
- **sales_by_contact** — contact_id → related sales  
- **reservations_by_contact** — contact_id → reservations  

Agents and Prism can then call these via MCP.

### 6. Laravel Scout + Typesense (full-text search)

Kit has **laravel/scout** and **typesense/typesense-php**; User is already Searchable. Add:

- **Step 1:** Contact — add **Searchable** + `toSearchableArray()`; configure Typesense collection for contacts.
- **Step 3:** Project, Lot — add Searchable; Typesense schema.
- **Step 4:** Sale — add Searchable.
- **Step 0:** Add Scout/Typesense production env vars to env checklist (e.g. TYPESENSE_HOST, API_KEY).

### 7. spatie/laravel-tags — do NOT create custom tags tables

Kit has **spatie/laravel-tags: ^4.11**. **Step 5** must **not** create custom `tags` and `taggables` tables. Use this package: add **HasTags** to Contact, Project, Sale, Task (or other taggable models). Import legacy tag data into Spatie’s schema (`tags`, `taggable` pivot). Custom tables would duplicate the kit package.

### 8. DataTable HasAi trait — every CRM table

The DataTable package has a **HasAi** trait giving each table: NLQ (natural language query), column insights, and Thesys C1 Visualize (AI charts from table data, opt-in). Every CRM DataTable (Contact, Project, Lot, Sale, Task, PropertyReservation) must add **HasAi**. **Step 0** must add env: **DATA_TABLE_AI_MODEL** and **THESYS_API_KEY** (required — without THESYS_API_KEY all AI responses render as plain text instead of interactive C1 cards).

### 8b. Thesys C1 — generative UI for all AI responses

**`THESYS_API_KEY`** (confirmed available). Thesys C1 is an OpenAI-compatible API that renders LLM responses as interactive UI components (cards, charts, tables, forms) instead of plain text. **Step 6** must install `@thesys/client` React SDK and register custom CRM components: ContactCard, PropertyCard, PipelineFunnel, EmailCompose, CommissionTable, TaskChecklist. All agent chat responses, AI insight cards, and NLQ results must render via C1, not raw text. See **rebuild-plan/00-thesys-conversational-ai.md** for full implementation spec. This is distinct from DataTable HasAi (which is table-specific); C1 is for all other AI surfaces.

### 9. filament/blueprint — preferred Filament scaffolding

Kit has **filament/blueprint: ^2.1.2**. Define Filament resources in YAML; run `composer setup-blueprint` (or `php artisan blueprint:build`) to scaffold PHP. Prefer this over hand-running `make:filament-resource` for speed. **CHIEF.md** and step files should reference Blueprint as the preferred way to scaffold Filament resources.

### 10. sync:route-permissions — correct permission commands

The kit uses **sync:route-permissions** (not `permission:sync-routes`). Also available: **permission:sync**, **permission:health**. **Step 1** (and any step that seeds or syncs permissions) must reference these correct commands.

---

## 🟠 P1 — High impact

### 11. spatie/laravel-schemaless-attributes — Contact.extra_attributes

**Step 1** Contact model: cast `extra_attributes` with **Spatie\SchemalessAttributes\Casts\SchemalessAttributes** (and `$appends = ['extra_attributes']` if needed). See kit `search-and-data.md`. Do not use a plain JSON cast only.

### 12. askedio/laravel-soft-cascade

- **Step 1:** On **Contact** model apply **SoftCascade** for relations `contact_emails`, `contact_phones` (soft-delete children when contact is soft-deleted).
- **Step 3:** On **Project** model apply SoftCascade for **lots**.

### 13. prism-php/relay — Prism → MCP

**Step 6:** Document that Prism ad-hoc generation can use **prism-php/relay** to call registered MCP tools (e.g. contacts_search, projects_search). See kit `docs/developer/backend/prism.md` Relay section.

### 14. Laravel Reverb (WebSockets)

**Step 7** (or a step between 7 and 14): Configure Reverb env; import Echo in frontend; define broadcast events and Echo listeners:

- Events: **TaskAssigned**, **ReservationStatusChanged**, **SaleStageChanged**, **ContactCreated** (or equivalent).
- Frontend: Echo listeners on task list, dashboard, reservation pages.

### 15a. jijunair/laravel-referral — Step 23

**Step 23:** Use **jijunair/laravel-referral** for referral code generation, tracking, and reward attribution on signup (signup form already has optional referral_code field).

### 15b. Sentry + Backup — Step 0 production

**Step 0:** Add to production/env checklist: **SENTRY_LARAVEL_DSN** (sentry/sentry-laravel); **spatie/laravel-backup** — configure `config/backup.php` with S3 (or destination) for production.

### 15c. akaunting/laravel-money — Monetary columns

**00-database-design** convention: Use **akaunting/laravel-money** for all monetary columns (price, commission amounts). Use `money()` helper or Money cast for display/API so formatting is consistent (projects/lots prices, sales commissions, etc.).

### 15d. Durable Workflows — laravel-workflow

- **Step 0 or new arch doc:** When to use **laravel-workflow/laravel-workflow** (and waterline) vs standard queued jobs.
- **Step 15** (AI lead nurture): Use Durable Workflow for multi-day drip sequences (wait days between steps).
- **Step 5** (mass import): Consider making `fusion:import-tasks-relationships-marketing` a workflow with per-table activities for resumability.

### 15e. Testing — composer test:quick per step

**12-ai-execution-and-human-in-the-loop.md:** At the end of each step, the AI must run **composer test:quick** (or composer test) and report pass/fail in the human checklist. Minimum: feature tests for import commands (dry-run + real where applicable); Pest tests for Filament resources (CRUD) as steps are implemented.

### 16. spatie/laravel-data — DTOs

Kit has **spatie/laravel-data: ^4.20**. All import-command DTOs, API response objects (Step 21 API v2), and DataTable DTOs must use **spatie/laravel-data** objects. This is the kit convention; do not use plain arrays or ad-hoc DTOs.

### 17. seeds:generate-ai and seeds:from-prose

**Step 0** should mention: `php artisan seeds:generate-ai` (AI-generated realistic seed data) and `php artisan seeds:from-prose "description"` (seeds from prose). Use for dev/demo data (contacts, projects, lots) without manual factories.

### 18. models:audit — model convention checker

**php artisan models:audit** checks models for missing conventions (userstamps, activity log, fillable, etc.). Run at the end of every step that adds models (Steps 1, 2, 3, 4, 5, 6) to verify CRM models are correctly set up.

### 19. HasAuditLog DataTable trait — compliance

Every DataTable with **HasAuditLog** writes to `data_table_audit_log` on inline edits/toggles. CRM tables (Sale, PropertyReservation, Contact) should use **HasAuditLog** for compliance. Add to ContactDataTable, SaleDataTable, Reservation DataTables.

### 20. laravel/boost — dev tool

Kit has **laravel/boost: ^2.3.1** (require-dev). **Step 0** env: **BOOST_ENABLED=true** (dev only). Reference **boost:update** post-install. See `config/boost.php`.

### 21. WithMemoryUnlessUnavailable — Step 6 agents

Kit has **App\Ai\Middleware\WithMemoryUnlessUnavailable** so memory recall works when embeddings are unavailable (e.g. OpenRouter without OpenAI embeddings). **Step 6** CRM agents (ContactAssistantAgent, PropertyAgent) must use this middleware, not the standard WithMemory.

### 22. laravel/wayfinder — frontend routes

Kit uses **laravel/wayfinder: ^0.1.14** for type-safe route generation in React. Inertia steps should use **route(ContactController.show, { id })** (or equivalent) instead of string URLs. Reference in step files that add Inertia pages.

---

## 🟡 P2 — Production quality

- **Step 0 env vars:** **DATA_TABLE_AI_MODEL**, **THESYS_API_KEY** (DataTable HasAi / Thesys Visualize); **BOOST_ENABLED** (dev); **ANALYTICS_PROPERTY_ID** (spatie/laravel-analytics, production). See Step 0 env checklist.
- **Step 3:** **spatie/laravel-sluggable** — add **HasSlug** to Project and Lot for website URLs (campaign websites, Step 21 API).
- **Step 21** (API v2): **spatie/laravel-query-builder** for filter/sort; **essa/api-tool-kit** for CRUD helpers. Run **php artisan generate:api-documentation** (Scramble) to auto-generate API docs; acceptance criterion for Step 21.
- **Step 23:** **beyondcode/laravel-vouchers: ^2.3** — optional for setup-fee discounts or promo codes during signup; complements jijunair/laravel-referral.
- **Step 0 production:** **spatie/laravel-responsecache: ^8.3** — use for public project/lot listing pages (Step 3/21) for performance.
- **Step 3:** **devrabiul/laravel-geo-genius** — kit has this; use for suburb/postcode validation or enrichment (Step 3 imports 15,299 suburbs; Step 5 has addresses).
- **Step 13** (Phase 2 email campaigns): Reference **backstage/laravel-mails** webhook setup (e.g. `php artisan mail:webhooks mailgun`) for bounce/delivery tracking.
- **Step 23:** Add **spatie/laravel-honeypot** to the public signup form and ProtectAgainstSpam middleware.
- **Step 5:** **resource_categories** — use **kalnoy/nestedset** for tree (parent/child categories).
- **Step 22** (Xero): Reference **laraveldaily/laravel-invoices** for invoice PDF generation.
- **Step 25** (sync): Reference **spatie/laravel-cronless-schedule** for local/dev sync testing without cron.

---

## 🔵 P3 — Optional / evaluate

- **genealabs/laravel-governor**: Evaluate for subscriber ownership scoping (Steps 2, 5, 6). Kit has it; consider for contact/project visibility by owner.
- **spatie/eloquent-sortable**: Use for **ai_bot_categories** sort_order (Step 6) and project list sort (Step 3) if kit uses it elsewhere.
- **spatie/laravel-model-flags**: Lightweight boolean flags without migrations. Could be used for Contact (e.g. is_verified, is_duplicate) or other models; evaluate if kit has it.

---

---

## 🟠 P1 — Additional Packages (from Live Site Audit)

### 23. Leaflet — Map View for Projects (Step 3)

`leaflet` + `react-leaflet` v4 + `@types/leaflet` — NOT in starter kit. Required for the 3rd layout mode on the Projects page (confirmed on live `/projects`). Add via bun:

```bash
bun add leaflet react-leaflet @types/leaflet
```

Optionally add `react-leaflet-cluster` for marker clustering at high zoom-out.

### 24. CKEditor — Landing Page Form Editor (Step 21)

`@ckeditor/ckeditor5-react` + `@ckeditor/ckeditor5-build-classic` — verify in starter kit `package.json`. Required for the campaign website landing page form-based editor (NOT Puck). If absent, add via bun:

```bash
bun add @ckeditor/ckeditor5-react @ckeditor/ckeditor5-build-classic
```

### 25. FingerprintJS — Same Device Detection (Step 7 reports)

`@fingerprintjs/fingerprintjs` (free OSS) — for the Same Device Detection fraud report. Captures browser fingerprint on login, stored as hashed value in `login_events` table. Add via bun:

```bash
bun add @fingerprintjs/fingerprintjs
```

### 26. New Feature Flag Classes — Step 0 (from Live Site Audit additions)

Add these to the Step 0 feature flag list alongside the existing flags:

- **`ApiAccessFeature`** — API Keys management (Step 2); gates `/settings/api-keys` and Sanctum token creation
- **`AiBotsCustomFeature`** — Custom bot creation in Bot In A Box (Step 5); subscribers can create custom bots if enabled
- **`FeaturedProjectsFeature`** — Featured Projects widget (Step 3); admin can mark projects as featured
- **`SprFeature`** — Special Property Request paid service ($55/request, Step 3); gates `/spr` submission form
- **`MapViewFeature`** — Map view on Projects page (Step 3); gated if Leaflet assets are too heavy for some plans

Register all new feature classes in `config/feature-flags.php` alongside existing `AiToolsFeature`, `PropertyAccessFeature`, etc.

### 27. TinyURL API — Campaign Websites (Step 21)

Add `TINYURL_API_KEY` to Step 0 env checklist. Used by campaign website save to generate/update short URLs. No new package — use Saloon or a simple HTTP request wrapper.

---

## Summary table

| Package / area           | Where in plan        | Action |
|--------------------------|----------------------|--------|
| legacy_lead_id + legacy_id | 00-database-design, Steps 1, 3, 4 | Columns in migrations |
| laravel-model-states + filament-statefusion | Steps 1, 4 | Contact stage; Sale/Reservation status |
| Pennant Feature classes | Step 0, Step 23      | App\Features\* + config |
| laravel-database-mail    | Steps 4, 5, 7, 23   | Events + config |
| laravel/mcp              | Step 6               | CRM tools in ApiServer |
| Scout/Typesense          | Step 0 env, Steps 1, 3, 4 | Searchable on Contact, Project, Lot, Sale |
| laravel-schemaless-attributes | Step 1        | Contact.extra_attributes cast |
| laravel-soft-cascade     | Steps 1, 3          | Contact→emails/phones; Project→lots |
| prism-php/relay          | Step 6              | Prism → MCP tools |
| Reverb                   | Step 7              | Broadcast events + Echo |
| laravel-referral          | Step 23             | Referral tracking |
| Sentry + Backup           | Step 0              | Production checklist |
| laravel-money             | 00-database-design  | Monetary columns convention |
| laravel-workflow          | Step 0/arch, 5, 15  | Imports + nurture sequences |
| test:quick                | Step 12             | Run at end of each step |
| spatie/laravel-tags       | Step 5              | HasTags on Contact, Project, Sale, Task; do NOT create custom tags/taggables |
| DataTable HasAi          | Step 0 env, Steps 1, 3, 4, 5, 7 | Every CRM DataTable; DATA_TABLE_AI_MODEL, THESYS_API_KEY |
| Thesys C1 (@thesys/client) | Step 6 (primary); Steps 1, 3, 7 | Generative UI for all AI agent responses; register ContactCard, PropertyCard, PipelineFunnel, EmailCompose, CommissionTable, TaskChecklist; see 00-thesys-conversational-ai.md |
| contacts.lead_score      | Steps 1 (column), 6 (AI compute) | 0–100 badge on contact list + record; AI-computed or rule-based |
| contacts.last_contacted_at | Step 1 | Days-Since-Contact badge + stale Smart List trigger |
| Smart List sidebar       | Step 1 (UI), Step 6 (AI suggestions) | Follow Up Boss model; saved filter sets as work queue; see 00-ui-design-system.md §7 |
| Dual pipeline Kanban     | Step 4 (data), Step 14 (UI) | Buyers + Sellers as separate boards; standard real estate CRM pattern |
| DataTable HasAuditLog    | Steps 1, 4, 5       | Contact, Sale, Reservation DataTables for compliance |
| filament/blueprint       | CHIEF, step files   | Prefer YAML + setup-blueprint for Filament scaffolding |
| sync:route-permissions   | Step 1              | Correct command (not permission:sync-routes); permission:sync, permission:health |
| spatie/laravel-data      | Steps 4, 5, 21      | DTOs for import, API, DataTables |
| seeds:generate-ai, seeds:from-prose | Step 0       | AI seed generation for dev/demo data |
| models:audit             | Steps 1–6           | Run after steps that add models |
| laravel/boost            | Step 0              | BOOST_ENABLED; boost:update |
| WithMemoryUnlessUnavailable | Step 6           | CRM agents middleware (not WithMemory) |
| laravel/wayfinder        | Inertia steps       | Type-safe routes in React |
| sluggable                | Step 3              | HasSlug on Project, Lot |
| generate:api-documentation | Step 21           | Scramble API docs |
| laravel-vouchers         | Step 23             | Optional promo/setup discounts |
| responsecache            | Step 0 prod         | Public project/lot pages |
| laravel-geo-genius       | Step 3              | Suburb/postcode validation |
| laravel-honeypot          | Step 23             | Signup form |
| nestedset                 | Step 5              | resource_categories |
| laravel-invoices          | Step 22             | Invoice PDFs |
| query-builder + api-tool-kit | Step 21          | API filtering / CRUD |
| cronless-schedule         | Step 25             | Local sync testing |
| genealabs/laravel-governor (P3) | Steps 2, 5, 6 | Evaluate for subscriber ownership scoping |
| spatie/eloquent-sortable (P3)   | Steps 3, 6         | ai_bot_categories sort_order; project sort |
| laravel/horizon (P2)            | Step 0 prod        | /horizon — monitor queue workers during import steps |
| spatie/laravel-csp (P2)         | Step 0 prod        | CSP headers for production security |
| spatie/laravel-model-flags (P3) | Step 1 / evaluate  | Contact is_verified, is_duplicate, etc. |
| leaflet + react-leaflet (P1)    | Step 3 (Map View)  | Add via bun; NOT in kit; map layout for /projects |
| @ckeditor/ckeditor5-react (P1)  | Step 21 (landing pages) | Structured form editor; verify in kit or add via bun |
| @fingerprintjs/fingerprintjs (P1) | Step 7 (reports)  | Same Device Detection; add via bun |
| TinyURL API key (env)           | Step 21            | TINYURL_API_KEY for campaign website short URLs |
| ApiAccessFeature (Pennant)      | Step 2             | Gates API key creation for subscribers |
| AiBotsCustomFeature (Pennant)   | Step 5             | Gates custom bot creation in Bot In A Box |
| FeaturedProjectsFeature (Pennant) | Step 3           | Featured Projects widget on dashboard |
| SprFeature (Pennant)            | Step 3             | Special Property Request ($55 paid service) |

---

## 🟢 DataTable Package — Trait & Configuration Reference

Full DataTable config per CRM section is in each step file's **DataTable Configuration** section. This entry covers cross-cutting conventions for `coding-sunshine/laravel-data-table` (59 commits as of Mar 14, 2026).

### Traits used across CRM DataTables

| Trait | Tables |
|---|---|
| `HasExport` | All tables (XLSX/CSV via Maatwebsite Excel; PDF via **spatie/laravel-pdf** — NOT dompdf) |
| `HasImport` | Contact |
| `HasInlineEdit` | Contact, Lot, Task (includes built-in undo/redo Ctrl+Z/Y, drag-to-fill, clipboard paste) |
| `HasToggle` | Contact (is_archived), Project (is_hot_property, is_archived), Lot (is_archived, title_status), Task (is_completed) |
| `HasSelectAll` | All tables |
| `HasAuditLog` | Contact, PropertyReservation, Sale (compliance logging); `php artisan data-table:audit-report` CLI available |
| `HasAi` | ALL CRM DataTables (see full AI methods below) |
| `HasReorder` | Project (sort within stage) |

### Required env vars (add to Step 0 checklist)

Set all of these in Step 0 so every subsequent step has them ready without interruption:

```env
# DataTable AI + Thesys C1 generative UI (required — all AI responses render via C1)
DATA_TABLE_AI_MODEL=openrouter/deepseek/deepseek-r1   # or gpt-4o-mini etc.
THESYS_API_KEY=                                         # required; without it AI responses are plain text

# AI providers (Prism + Laravel AI SDK)
OPENROUTER_API_KEY=        # Prism ad-hoc generation (DeepSeek R1 free default)
OPENAI_API_KEY=            # Laravel AI SDK embeddings (text-embedding-3-small, 1536 dims)

# RAG reranking — Step 6 agent memory recall
COHERE_API_KEY=            # rerank-english-v3.0; cosine threshold 0.5, top 5

# Reverb WebSockets — Step 6+ real-time broadcast events
REVERB_APP_ID=
REVERB_APP_KEY=
REVERB_APP_SECRET=
REVERB_HOST=localhost
REVERB_PORT=8080
REVERB_SCHEME=http
VITE_REVERB_APP_KEY="${REVERB_APP_KEY}"
VITE_REVERB_HOST="${REVERB_HOST}"
VITE_REVERB_PORT="${REVERB_PORT}"
VITE_REVERB_SCHEME="${REVERB_SCHEME}"

# Scout / Typesense — full-text search (Contact, Project, Lot); add before Step 1
SCOUT_DRIVER=typesense
TYPESENSE_HOST=localhost
TYPESENSE_PORT=8108
TYPESENSE_PROTOCOL=http
TYPESENSE_API_KEY=
```

### `tableAiSystemContext()` — every table must define this

See step files (Steps 1, 3, 4, 5, 7) for the exact strings. The context sets the domain knowledge for the HasAi NLQ handler. Without it, the AI generates generic SQL that misses domain nuance (e.g. "stale" = last_contacted_at > 30 days, not last updated_at).

### HasAi — full method list (all methods Chief must implement per CRM DataTable)

| Method | Required | Description |
|---|---|---|
| `tableAiSystemContext(): string` | ✅ Yes | Domain instructions for all AI handlers — see step files for CRM-specific strings |
| `tableAiModel(): ?string` | Optional | Override LLM model per table. Default: respects app's Prism config (`DATA_TABLE_AI_MODEL`) |
| `tableAiSampleSize(): int` | Optional | Max rows sent to LLM. Default: 50 |
| `handleAiQuery(string, Request)` | Auto | NLQ → filters/sort (built-in handler, override only for custom logic) |
| `handleAiInsights(Request)` | Auto | Anomaly/trend/pattern detection over current data |
| `handleAiSuggest(Request)` | Auto | Smart filter and sort suggestions |
| `handleAiColumnSummary(string, Request)` | Auto | Per-column analysis (click column header → AI explains patterns) |
| `handleAiEnrich(string, string, array, Request)` | Auto | Bulk-fill missing column values with AI-generated data |
| `handleAiVisualize(Request)` | Auto | Thesys C1 generative UI — renders chart/table/card from data |

All `handle*` methods are provided by the `HasAi` trait. Override only if CRM logic differs from the default. `tableAiSystemContext()` is the key one to define — all other handlers use it as context.

### New features now built into the package (as of Mar 2026)

These were previously noted as gaps or required custom code — they are now **built in**:

| Feature | How to use | CRM usage |
|---|---|---|
| **Kanban board view** | Multi-layout switcher: `layout: 'kanban'`, grouped by a status column, drag-to-move cards | Reservations/Sales pipeline (Step 4); Deal Tracker board (Step 14) |
| **Multi-layout switcher** | Table / Grid / Cards / Kanban views toggle in toolbar | Projects grid (Step 3) — use built-in Grid view instead of custom ToggleGroup |
| **Detail rows / drawer** | `tableDetailDisplay(): 'inline'\|'modal'\|'drawer'` | Lot inventory inside Project row → use `'drawer'` instead of custom Sheet |
| **Per-table AI model** | `tableAiModel(): string` | Use cheaper model for Contacts, stronger for Reports AI |
| **Row click handler** | `onRowClick` callback or `rowLink` prop on React side | Contact row → navigate to contact detail page |
| **Undo/redo** | Built into `HasInlineEdit` — Ctrl+Z / Ctrl+Y | All inline-edit tables (Contact, Lot, Task) |
| **Column pinning** | Pin columns left/right via column header menu | Pin Name column on Contact/Project tables |
| **Keyboard shortcuts overlay** | Press `?` — shows all shortcuts; Ctrl+F focuses search | All tables (zero config needed) |
| **Saved Views** | User-saved filter/sort/column presets (localStorage or DB) | All tables — publish migrations for DB persistence |
| **Real-time updates** | Laravel Echo integration — table auto-refreshes on broadcast events | Contact table refreshes on `ContactUpdated` event (Reverb) |
| **Action groups** | `group` property on row actions for nested submenu dropdowns | Contact actions: "More → [Tag, Archive, Assign]" |
| **Sparklines** | Inline SVG mini-charts (line/bar) in cells | Lead score trend in Contact table |
| **Cascading filters** | Filter options depend on parent filter value | State → Suburb cascade on Projects filter |
| **Async filter options** | Lazy-load filter options from server with search | Agent filter (lazy-load users list) |
| **PDF export** | `spatie/laravel-pdf` (NOT dompdf) — already in starter kit | All `HasExport` tables |
| **i18n / translations** | `php artisan data-table:translations --lang=en` | Not needed for English-only CRM |
| **`tablePinnedTopRows()`** | Pin specific rows to top (e.g. "overdue" contacts) | Pin priority contacts at top of Contact table |

### Still custom (not built into DataTable package)

| Gap | Workaround |
|---|---|
| `tableAiSystemContext()` cascade from parent (embedded tables) | Pass parent context manually in the embedded table's `tableAiSystemContext()` override |
| Quick view tab badge counts | Add count to QuickView `label` server-side: `label: "Overdue (12)"` |
| AI NLQ quick-prompt chips | Add as static chips above NLQ input in the React page component |
