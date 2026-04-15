# FusionCRM v4

## Project Overview
- Property/real-estate CRM rebuilt from v3 (MySQL) to v4 (PostgreSQL)
- Starter kit base: laravel-starter-kit-inertia-react
- Stack: Laravel 13, PostgreSQL 16, Inertia + React, Filament 5

## Critical Rules
- NEVER touch sites/default/sqlconf.php
- NEVER commit without human approval
- ALL CRM code in modules/module-crm/ (namespace Cogneiss\ModuleCrm)
- ALL CRM models MUST have organization_id (use BelongsToOrganization trait)
- ALL syncable models MUST implement SyncableContract
- Use Eloquent only — no DB::raw() in CRM code (sync depends on observers)
- Use laravel/ai (not prism-php/prism directly)
- **Filament = superadmin system settings ONLY** (at `/admin` and `/system`)
- **ALL user/org-admin/agent/builder-facing features = Inertia + React** (never Filament)
- Roles/permissions use `spatie/laravel-permission` (Governor was removed from starter kit April 2026)

## Companion Docs (in `v4-schema/` directory)
- `v4-schema/newdb.md` — full DDL schema (source of truth for all tables, including §3.31-3.42 builder portal tables)
- `v4-schema/newdb-simple.md` — visual column migration map
- `v4-schema/sync-architecture.md` — bidirectional sync design
- `v4-schema/dbissues.md` — v3 problems to avoid
- `v4/specs/kenekt-competitive-feature-spec.md` — Kenekt competitive analysis (22 features)

When PRDs reference "newdb.md §3.1" or "sync-architecture.md §5", look in `v4-schema/`.

## Launch Strategy (2026-03-25)

**Phase 1 — Builder Portal (SHIP FIRST):**
- Builder Portal launches first. Subscriber migration happens later.
- Builders self-signup → create stock → agents browse portal
- Stock auto-pushes to v3 as enriched projects + lots (zero v3 code changes)
- Subscribers see builder stock in their existing v3 project/lot views
- Design doc: `v4/specs/2026-03-25-builder-portal-fast-track-design.md`
- Competitive spec: `v4/specs/kenekt-competitive-feature-spec.md`

**Phase 2 — Subscriber Migration:** Import v3 data, full sync, subscriber CRM features.
**Phase 3 — Advanced Features:** AI, analytics, marketing, Xero, R&D.

### Builder Portal Key Facts
- **Competing with Kenekt** ($799-990/mo) — Fusion offers builder portal FREE
- **PRD 12** = the product (20 user stories, 3 tiers)
- **Two systems**: Builder Workbench (stock management) + Agent Portal (stock consumption)
- **13 new tables**: construction_designs, construction_facades, construction_inclusions, construction_inclusion_items, commission_templates, packages, package_inclusions, package_distributions, agent_favorites, agent_saved_searches, sale_commission_payments, enquiries, stock_imports
- **v3 Push**: `PackageV3PushObserver` writes to v3 projects + lots on create/update/delete
- **Self-signup**: Builder registers → User + Organization (type='developer') + subdomain

### Phase 1 Build Order
```
Pre-Flight → Step 0a (full) → Step 0b (full) → Step 2-slim (models+auth)
→ Step 3-slim (models only) → Step 4-slim (models only)
→ Step 1.5-slim (v3 push only) → PRD 12 Tier 1 → LAUNCH
```

### What is SKIPPED in Phase 1
- Step 1 (Contacts — 9,735 imports) → Phase 2
- Step 1.5 full sync (bidirectional) → Phase 2
- Steps 2/3/4 v3 data imports → Phase 2
- Steps 5-7 (notes, AI, dashboards) → Phase 2
- PRD 12 Tiers 2-3 → Phase 3
- **Construction Library** = relational engine (designs -> facades -> inclusions), NOT a media folder
- **Packages** = lot + design + facade + inclusions with auto-calculated pricing
- **Distribution** = open (all agents) or exclusive (selected agents only)

## Design System

### Brand Tokens
| Token        | Hex       | Use |
|-------------|-----------|-----|
| Primary     | `#f28036` | Warm orange — buttons, links, active states, focus rings |
| Body bg     | `#f3f1ee` | Warm off-white, not clinical pure white |
| Nav / card  | `#f9f8f7` | Barely-warm white |
| Text body   | `#727E8C` | Blue-gray body |
| Headings    | `#475F7B` | Dark blue-gray |
| Border      | `#DFE3E7` | Light gray |

### Tailwind v4 CSS Variables
```css
:root {
  --color-primary: oklch(70% 0.18 48);           /* #f28036 */
  --color-primary-foreground: oklch(98% 0 0);
  --color-background: oklch(96% 0.005 70);       /* #f3f1ee */
  --color-card: oklch(98% 0.003 70);             /* #f9f8f7 */
  --color-foreground: oklch(45% 0.05 240);       /* #475F7B */
  --color-muted-foreground: oklch(55% 0.04 240); /* #727E8C */
  --color-border: oklch(88% 0.02 240);           /* #DFE3E7 */
  --color-status-new: oklch(65% 0.15 240);
  --color-status-qualified: oklch(65% 0.18 160);
  --color-status-proposal: oklch(70% 0.18 48);
  --color-status-converted: oklch(60% 0.20 145);
  --color-status-archived: oklch(70% 0.02 240);
  --color-status-reserved: oklch(65% 0.18 30);
  --color-status-sold: oklch(55% 0.20 145);
  --color-status-cancelled: oklch(55% 0.18 15);
}
```

Use CSS variables everywhere — no hardcoded hex or `text-gray-*` classes.

### Typography
- **Font:** Geist Sans (`THEME_FONT=geist-sans`). Never Inter, Roboto, or Arial.
- Page title: `text-xl font-semibold`
- Section heading: `text-base font-medium`
- Body: `text-sm font-normal` (14px minimum)
- Small/meta: `text-xs font-normal`
- KPI stat: `text-2xl font-bold`
- No uppercase labels with `tracking-widest`. Sentence case only.
- No eyebrow labels above headings.

### Spacing & Density
- shadcn base color: zinc, CSS vars: true, radius: 0.5rem
- CRM tables: compact rows (`text-sm`, `py-1.5`), data-first density
- Status indicators: 8px colored dot + label (StatusDot), NOT Badge pills
- Days-Since-Contact color coding: 0-6d green, 7-29d amber, 30-89d orange, 90+d red

### Design Principles
| Principle | Rule |
|-----------|------|
| Data-first density | Compact tables, not spacious cards |
| Orange primary | All CTAs use `--color-primary` (#f28036) |
| Warm neutral surfaces | bg #f3f1ee, not pure white |
| Left-anchored layouts | Sidebar + content, never top-only nav |
| Status as dots | Colored dot + label, not Badge pills |
| Dark mode parity | CSS vars only, no hardcoded colors |
| AI as natural affordance | Floating AI panel on every page |

### Logo
- File: `public/images/logo/logo.png`
- Sidebar: `<img src="/images/logo/logo.png" alt="Fusion CRM" className="h-9 w-auto" />`
- Filament: `->brandLogo(asset('images/logo/logo.png'))->brandLogoHeight('36px')`

## Component Map

### File Structure
```
resources/js/
  Pages/
    Dashboard/       Index.tsx
    Contacts/        Index.tsx  Show.tsx  Create.tsx  Edit.tsx
    Projects/        Index.tsx  Show.tsx
    Lots/            Show.tsx   (slide-over inside Projects)
    Reservations/    Index.tsx  Show.tsx
    Sales/           Index.tsx  Show.tsx
    Tasks/           Index.tsx
    Reports/         Index.tsx  Show.tsx
    Flyers/          Index.tsx  Show.tsx
    MailLists/       Index.tsx  Show.tsx
    BotInABox/       Index.tsx  Run.tsx
    Websites/        Index.tsx
    Settings/        ApiKeys.tsx
  Components/
    Layout/
      AppLayout.tsx         — main shell: sidebar + page-header slot
      PageHeader.tsx        — breadcrumb + title + right-actions slot
      AppSidebar.tsx        — 220px sidebar, collapsible to 56px icon-only
    Ui/                     — shadcn generated components (do not edit)
    Shared/
      SmartListSidebar.tsx  — contact list left panel
      DataTable.tsx         — reusable table with HasAi NLQ + pagination
      StatusDot.tsx         — 8px dot + label (never Badge)
      DaysSinceBadge.tsx    — color-coded recency badge
      LeadScoreBadge.tsx    — 0-100 score badge
      AiChatPanel.tsx       — floating AI assistant (Sheet, 400px)
      ActivityTimeline.tsx  — unified chronological feed
      MilestoneTimeline.tsx — vertical step tracker
      ThesysC1Renderer.tsx  — renders AI responses via @thesys/client
```

### shadcn Install Checklist (Step 0)
```bash
npx shadcn@latest add card sheet separator breadcrumb
npx shadcn@latest add button input textarea label select checkbox form combobox
npx shadcn@latest add badge toast sonner skeleton
npx shadcn@latest add table
npx shadcn@latest add dialog dropdown-menu popover tooltip
npx shadcn@latest add toggle-group tabs
```

### Key Pages
- Dashboard: 3 action-required counts + priority work queue + pipeline funnel + KPI cards
- Contact List: SmartListSidebar (220px) + HasAi NLQ bar + DataTable (compact 40px rows)
- Contact Detail: 2-col (60/40) — unified timeline (NO tabs) + sidebar with stage stepper
- Project List: Grid default (photo cards), slide-over for lots (no page nav)
- Reservation Detail: 6-step milestone timeline (Enquiry > Qualified > Reservation > Contract > Unconditional > Settled)
- Tasks: Grouped by Due Today / Overdue / Upcoming, inline complete checkbox

## UI Pattern: DataTable

Every CRM list page uses this stack:

```
Backend:
  DataTable class  -> modules/module-crm/src/DataTables/{Entity}DataTable.php
                      extends AbstractDataTable (machour/laravel-data-table)
                      defines: tableColumns(), inertiaProps(), showProps()
  Controller       -> modules/module-crm/src/Http/Controllers/{Entity}Controller.php
                      index() calls {Entity}DataTable::inertiaProps($request)
                      returns Inertia::render('crm/{entity}/index', $props)
  Routes           -> modules/module-crm/routes/web.php

Frontend:
  Index page       -> resources/js/pages/crm/{entity}/index.tsx
                      <AppSidebarLayout> + <DataTable<EntityRow>>
  Show page        -> resources/js/pages/crm/{entity}/show.tsx
  Create/Edit      -> resources/js/pages/crm/{entity}/create.tsx, edit.tsx
  TypeScript types -> resources/js/types/crm/{entity}.ts
```

DataTable column definition:
```php
ColumnBuilder::make('first_name', 'First Name')->searchable()->sortable()->type('text')->build(),
ColumnBuilder::make('stage', 'Stage')->type('option')->options([...])->filterable()->build(),
```

Key frontend components: `<DataTable<T>>`, `<AppSidebarLayout>`, `useForm()` from `@inertiajs/react`, Wayfinder typed route helpers.

## Import Command Pattern

### Base Structure
All `fusion:import-*` commands follow this pattern:
- Flags: `--dry-run`, `--chunk=500`, `--since=`, `--force`
- Connection: `mysql_legacy` from `config/database.php`
- Progress bar, chunked transactions, per-row error logging
- `mapRow(array $row): ?array` — return null to skip
- `upsertRow(array $data, bool $force): void` — updateOrInsert by legacy key

### Lead ID -> Contact ID Map
```php
protected function buildLeadContactMap(): array
{
    return DB::table('contacts')
        ->whereNotNull('legacy_lead_id')
        ->pluck('id', 'legacy_lead_id')
        ->all();
}
```
All import commands after Step 1 MUST use this map. Stored via `contacts.legacy_lead_id` column (indexed).

### Idempotency Rules
1. `updateOrCreate` on stable business key (legacy_id / legacy_lead_id / email / slug)
2. Never raw INSERT — always `updateOrInsert` or `upsert`
3. Map rebuilt fresh from DB each run
4. Failed rows logged and skipped; next run retries
5. `--dry-run` never writes to DB

### Verification Commands
Every import has a paired `fusion:verify-import-*` command that:
- Compares row counts against expected values
- Checks for orphan FKs
- Outputs PASS/FAIL per check with exit code 0/non-zero

### Import Order
```bash
php artisan fusion:import-projects-lots          # Step 3
php artisan fusion:import-contacts               # Step 1
php artisan fusion:import-users                  # Step 2
php artisan fusion:import-reservations-sales     # Step 4
php artisan fusion:import-tasks-relationships-marketing  # Step 5
php artisan fusion:import-ai-bot-config          # Step 6
```

## AI Configuration

### Credit System
- **AI Credit**: One unit per AI interaction. Deducted per action.
- **Plan Credits**: Default 100 per org per billing period (configurable by superadmin)
- **BYOK**: Bring Your Own Key (OpenAI/OpenRouter) — bypasses credit system entirely
- **Credit Period**: Monthly reset, no rollover

Credit costs per action (configured in `config/ai-credits.php`):
| Action | Credits |
|--------|---------|
| Chat message | 1 |
| DataTable NLQ query | 1 |
| DataTable AI insight | 2 |
| Email draft generation | 2 |
| Lead score computation | 1 |
| Property match suggestion | 1 |
| Dashboard AI insight | 2 |
| Bulk AI enrich (per 10 rows) | 5 |

Key service: `AiCreditService` with methods: `canUse()`, `deduct()`, `getBalance()`, `resetPeriod()`, `getByokKey()`, `setByokKey()`

Frontend: Credit widget in AppSidebar bottom, per-page tooltip on NLQ bar, upgrade modal on exhaustion, BYOK badge for own-key users.

### Thesys C1 Generative UI
Thesys C1 renders AI responses as interactive UI components instead of plain text.

- **API style**: OpenAI-compatible (drop-in replacement)
- **Frontend SDK**: `@thesys/client` React SDK
- **Auth**: `THESYS_API_KEY` in `.env`
- **DataTable HasAi**: Automatically wired via `THESYS_API_KEY` — NLQ + Visualize on every HasAi DataTable

**CRITICAL**: Never render AI responses as plain `<p>` text. Always use `<ThesysC1Renderer>` or `<C1 />` wrapper.

Custom CRM components registered with C1:
- `ContactCard` — contact summary with stage dot + actions
- `PropertyCard` — lot/project card with photo + price
- `PipelineFunnel` — funnel chart from stage counts
- `EmailCompose` — email card with subject/body/send
- `CommissionTable` — sale commission breakdown
- `TaskChecklist` — task card with check/assign actions

Env vars:
```env
THESYS_API_KEY=your-thesys-api-key
DATA_TABLE_AI_MODEL=gpt-4o-mini
OPENAI_API_KEY=...
```

### AI Execution Rules (Human-in-the-Loop)
- One step at a time. Implement only the step you were asked to do.
- Starter kit is source of truth for patterns and conventions.
- After completing a step: run verification + `composer test:quick`, then STOP for human approval.
- Chief/autonomous mode: skip human approval between steps if verification passes.
- Self-healing protocol: diagnose -> fix -> re-run, up to 3 attempts per failure.
- Mid-build stops only for: destructive actions not in plan, or genuinely missing credentials.
- All `fusion:import-*` commands must support `--dry-run` and `--chunk`.
- All `fusion:verify-import-*` commands must produce machine-parseable output with exit code 0 (PASS) or non-zero (FAIL).

## Visual QA Protocol

Run after every task that produces Inertia pages. Uses Playwright MCP for screenshots.

### Universal Checks (every page)
| Check | Pass condition |
|-------|---------------|
| U1: No console errors | `getConsoleErrors` returns [] |
| U2: No network errors | No 4xx/5xx responses (except 404 favicon) |
| U3: Brand orange applied | `--color-primary` resolves to oklch near #f28036 |
| U4: Font is Geist Sans | Computed font-family contains "Geist" |
| U5: Page title set | Not "Laravel" or empty |
| U6: Accessibility score | >= 85 (70-84 = WARN, <70 = FAIL) |
| U7: No emoji icons | No emoji characters in button/icon elements |

### Self-Healing Protocol
1. Read console errors / laravel.log
2. Identify root cause from stack trace
3. Fix and reload
4. Re-run failing check
5. Up to 3 attempts; if still failing, log as `VISUAL QA FAIL` and continue

Screenshots saved to `storage/app/qa/`.

## Multi-Tenancy
- Each v3 subscriber -> v4 Organization
- PIAB org = platform superadmin org for shared data
- contact_origin: 'saas_product' (PIAB) | 'property' (subscriber)
- Project visibility: PIAB org = shared marketplace, subscriber org = scoped
- `BelongsToOrganization` trait + `OrganizationScope` global scope on all CRM models
- Superadmin/piab_admin bypass org scope via `withoutGlobalScope`
- Organization types: 'platform' (PIAB, 1 org), 'developer' (creates projects/lots), 'agency' (sells properties)
- Developers get own subdomain via organization_domains (e.g., metricon.fusioncrm.com)
- Agencies get own subdomain (e.g., flexiproperty.fusioncrm.com)
- Stock view = developer manages their projects/lots
- Portal view = agents browse available stock from developers

## Sync
- Bidirectional during parallel run, see sync-architecture.md
- 8 bidirectional entities, 4 one-way, event-driven + polled + batch
- All sync code in modules/module-crm/src/Sync/
- Config: `config/sync.php` — piab_organization_id, tiebreaker_source (v3), batch_chunk_size, poll_interval


---

# Laravel Boost / Starter Kit Guidelines

> Auto-generated by Laravel Boost. Regenerate with `php artisan boost:install` (update mode).
> FusionCRM-specific rules above take precedence on conflict.

<laravel-boost-guidelines>
=== .ai/app.actions rules ===

# App/Actions guidelines

- This application uses the Action pattern and prefers for much logic to live in reusable and composable Action classes.
- Actions live in `app/Actions`, they are named based on what they do, with no suffix.
- Actions will be called from many different places: jobs, commands, HTTP requests, API requests, MCP requests, and more.
- Create dedicated Action classes for business logic with a single `handle()` method.
- Inject dependencies via constructor using private properties.
- Create new actions with `php artisan make:action "{name}" --no-interaction`
- Wrap complex operations in `DB::transaction()` within actions when multiple models are involved.
- Some actions won't require dependencies via `__construct` and they can use just the `handle()` method.

<!-- Example action class -->
```php
<?php

declare(strict_types=1);

namespace App\Actions;

final readonly class CreateFavorite
{
    public function __construct(private FavoriteService $favorites)
    {
        //
    }

    public function handle(User $user, string $favorite): bool
    {
        return $this->favorites->add($user, $favorite);
    }
}
```

=== .ai/documentation rules ===

# Documentation Guidelines

## When to Document

Document new Actions, Controllers, Pages, and Routes. Skip documentation for bug fixes, refactors, and UI-only changes unless they change workflows.

## Documentation Locations

| Component | Location |
|-----------|----------|
| Actions | `docs/developer/backend/actions/` |
| Controllers | `docs/developer/backend/controllers/` |
| Pages | `docs/developer/frontend/pages/` |
| Routes | `docs/developer/api-reference/routes.md` |
| User-facing features | `docs/user-guide/` |

## Templates

Templates in `docs/.templates/`: `action.md`, `controller.md`, `page.md`, `user-feature.md`.

## Manifest Sync

Run `php artisan docs:sync` to update `docs/.manifest.json` with codebase state and relationships.

- `--check`: Check for undocumented items without updating
- `--generate`: Create documentation stubs for undocumented items

## Cross-Referencing

The manifest tracks relationships automatically:

- **Actions**: Which controllers use them, which models they use, which routes call them
- **Controllers**: Which actions they use, which form requests, which routes, which pages they render
- **Pages**: Which controllers render them, which routes lead to them

=== .ai/general rules ===

# General Guidelines

- Don't include any superfluous PHP Annotations, except ones that start with `@` for typing variables.

=== .ai/settings rules ===

# Settings & Configuration Guidelines

- Runtime configuration is DB-backed via **spatie/laravel-settings**. Settings classes live in `app/Settings/` and are managed via Filament admin pages.
- Never use `env()` outside of config files. Use `config('key')` — the `SettingsOverlayServiceProvider` writes DB values into config at boot.
- When adding a new setting: create the Settings class, create a settings migration in `database/settings/`, add the mapping to `SettingsOverlayServiceProvider::OVERLAY_MAP`, and create a Filament `SettingsPage`.
- Settings that hold secrets (API keys, passwords) must define `public static function encrypted(): array` returning the encrypted property names.
- 7 groups are org-overridable (Billing, Mail, Stripe, Paddle, LemonSqueezy, Prism, AI). Set `'orgOverridable' => true` in `OVERLAY_MAP` to enable per-org overrides.
- Per-org overrides are stored in the `organization_settings` table and applied by `ApplyOrganizationSettings` middleware after `SetTenantContext`.
- Infrastructure settings (`APP_KEY`, `DB_*`, `CACHE_STORE`, `SESSION_DRIVER`, `QUEUE_CONNECTION`, `REDIS_*`, `LOG_CHANNEL`) stay in `.env` — they are needed before the DB is available.
- See `docs/developer/backend/settings.md` for full documentation.

=== foundation rules ===

# Laravel Boost Guidelines

The Laravel Boost guidelines are specifically curated by Laravel maintainers for this application. These guidelines should be followed closely to ensure the best experience when building Laravel applications.

## Foundational Context

This application is a Laravel application and its main Laravel ecosystems package & versions are below. You are an expert with them all. Ensure you abide by these specific packages & versions.

- php - 8.4
- filament/filament (FILAMENT) - v5
- inertiajs/inertia-laravel (INERTIA_LARAVEL) - v3
- laravel/ai (AI) - v0
- laravel/fortify (FORTIFY) - v1
- laravel/framework (LARAVEL) - v13
- laravel/horizon (HORIZON) - v5
- laravel/mcp (MCP) - v0
- laravel/pennant (PENNANT) - v1
- laravel/prompts (PROMPTS) - v0
- laravel/pulse (PULSE) - v1
- laravel/reverb (REVERB) - v1
- laravel/sanctum (SANCTUM) - v4
- laravel/scout (SCOUT) - v11
- laravel/socialite (SOCIALITE) - v5
- laravel/wayfinder (WAYFINDER) - v0
- livewire/livewire (LIVEWIRE) - v4
- larastan/larastan (LARASTAN) - v3
- laravel/boost (BOOST) - v2
- laravel/pail (PAIL) - v1
- laravel/pint (PINT) - v1
- pestphp/pest (PEST) - v4
- phpunit/phpunit (PHPUNIT) - v12
- rector/rector (RECTOR) - v2
- @inertiajs/react (INERTIA_REACT) - v3
- laravel-echo (ECHO) - v2
- react (REACT) - v19
- tailwindcss (TAILWINDCSS) - v4
- @laravel/vite-plugin-wayfinder (WAYFINDER_VITE) - v0
- eslint (ESLINT) - v10
- prettier (PRETTIER) - v3

## Skills Activation

This project has domain-specific skills available. You MUST activate the relevant skill whenever you work in that domain—don't wait until you're stuck.

- `ai-sdk-development` — TRIGGER when working with ai-sdk which is Laravel official first-party AI SDK. Activate when building, editing AI agents, chatbots, text generation, image generation, audio/TTS, transcription/STT, embeddings, RAG, vector stores, reranking, structured output, streaming, conversation memory, tools, queueing, broadcasting, and provider failover across OpenAI, Anthropic, Gemini, Azure, Groq, xAI, DeepSeek, Mistral, Ollama, ElevenLabs, Cohere, Jina, and VoyageAI. Invoke when the user references ai-sdk, the `Laravel\Ai\` namespace, or this project's AI features — not for Prism PHP or other AI packages used directly.
- `fortify-development` — ACTIVATE when the user works on authentication in Laravel. This includes login, registration, password reset, email verification, two-factor authentication (2FA/TOTP/QR codes/recovery codes), profile updates, password confirmation, or any auth-related routes and controllers. Activate when the user mentions Fortify, auth, authentication, login, register, signup, forgot password, verify email, 2FA, or references app/Actions/Fortify/, CreateNewUser, UpdateUserProfileInformation, FortifyServiceProvider, config/fortify.php, or auth guards. Fortify is the frontend-agnostic authentication backend for Laravel that registers all auth routes and controllers. Also activate when building SPA or headless authentication, customizing login redirects, overriding response contracts like LoginResponse, or configuring login throttling. Do NOT activate for Laravel Passport (OAuth2 API tokens), Socialite (OAuth social login), or non-auth Laravel features.
- `laravel-best-practices` — Apply this skill whenever writing, reviewing, or refactoring Laravel PHP code. This includes creating or modifying controllers, models, migrations, form requests, policies, jobs, scheduled commands, service classes, and Eloquent queries. Triggers for N+1 and query performance issues, caching strategies, authorization and security patterns, validation, error handling, queue and job configuration, route definitions, and architectural decisions. Also use for Laravel code reviews and refactoring existing Laravel code to follow best practices. Covers any task involving Laravel backend PHP code patterns.
- `configuring-horizon` — Use this skill whenever the user mentions Horizon by name in a Laravel context. Covers the full Horizon lifecycle: installing Horizon (horizon:install, Sail setup), configuring config/horizon.php (supervisor blocks, queue assignments, balancing strategies, minProcesses/maxProcesses), fixing the dashboard (authorization via Gate::define viewHorizon, blank metrics, horizon:snapshot scheduling), and troubleshooting production issues (worker crashes, timeout chain ordering, LongWaitDetected notifications, waits config). Also covers job tagging and silencing. Do not use for generic Laravel queues without Horizon, SQS or database drivers, standalone Redis setup, Linux supervisord, Telescope, or job batching.
- `mcp-development` — Develops MCP servers, tools, resources, and prompts. Activates when creating MCP tools, resources, or prompts; setting up AI integrations; debugging MCP connections; working with routes/ai.php; or when the user mentions MCP, Model Context Protocol, AI tools, AI server, or building tools for AI assistants.
- `pennant-development` — Manages feature flags with Laravel Pennant. Activates when creating, checking, or toggling feature flags; showing or hiding features conditionally; implementing A/B testing; working with @feature directive; or when the user mentions feature flags, feature toggles, Pennant, conditional features, rollouts, or gradually enabling features.
- `pulse-development` — Handles Laravel Pulse setup, configuration, and custom card development. Activates when installing Pulse; configuring the dashboard or authorization gate; setting up recorders and filtering; building custom Livewire cards; optimizing with Redis ingest or sampling; or when the user mentions /pulse, pulse:check, pulse:work, Pulse::record(), or application monitoring.
- `scout-development` — Develops full-text search with Laravel Scout. Activates when installing or configuring Scout; choosing a search engine (Algolia, Meilisearch, Typesense, Database, Collection); adding the Searchable trait to models; customizing toSearchableArray or searchableAs; importing or flushing search indexes; writing search queries with where clauses, pagination, or soft deletes; configuring index settings; troubleshooting search results; or when the user mentions Scout, full-text search, search indexing, or search engines in a Laravel project. Make sure to use this skill whenever the user works with search functionality in Laravel, even if they don't explicitly mention Scout.
- `socialite-development` — Manages OAuth social authentication with Laravel Socialite. Activate when adding social login providers; configuring OAuth redirect/callback flows; retrieving authenticated user details; customizing scopes or parameters; setting up community providers; testing with Socialite fakes; or when the user mentions social login, OAuth, Socialite, or third-party authentication.
- `wayfinder-development` — Activates whenever referencing backend routes in frontend components. Use when importing from @/actions or @/routes, calling Laravel routes from TypeScript, or working with Wayfinder route functions.
- `pest-testing` — Tests applications using the Pest 4 PHP framework. Activates when writing tests, creating unit or feature tests, adding assertions, testing Livewire components, browser testing, debugging test failures, working with datasets or mocking; or when the user mentions test, spec, TDD, expects, assertion, coverage, or needs to verify functionality works.
- `inertia-react-development` — Develops Inertia.js v2 React client-side applications. Activates when creating React pages, forms, or navigation; using <Link>, <Form>, useForm, or router; working with deferred props, prefetching, or polling; or when user mentions React with Inertia, React pages, React forms, or React navigation.
- `echo-development` — Develops real-time broadcasting with Laravel Echo. Activates when setting up broadcasting (Reverb, Pusher, Ably); creating ShouldBroadcast events; defining broadcast channels (public, private, presence, encrypted); authorizing channels; configuring Echo; listening for events; implementing client events (whisper); setting up model broadcasting; broadcasting notifications; or when the user mentions broadcasting, Echo, WebSockets, real-time events, Reverb, or presence channels.
- `tailwindcss-development` — Styles applications using Tailwind CSS v4 utilities. Activates when adding styles, restyling components, working with gradients, spacing, layout, flex, grid, responsive design, dark mode, colors, typography, or borders; or when the user mentions CSS, styling, classes, Tailwind, restyle, hero section, cards, buttons, or any visual/UI changes.
- `developing-with-prism` — Guide for the narrow Prism/Relay role in this project. Activate ONLY when working with MCP tool integration via `PrismService::withTools()` (Relay bridge). For all other AI features — text generation, structured output, images, audio, embeddings, streaming, tools, conversation memory — use `developing-with-ai-sdk` instead.
- `database-mail` — Database-backed email templates with martinpetricko/laravel-database-mail. Activates when adding events that should send emails from DB templates; creating or editing mail templates; or when the user mentions database mail, email templates, event-triggered emails, or TriggersDatabaseMail.
- `developing-with-ai-sdk` — Builds AI agents, generates text and chat responses, produces images, synthesizes audio, transcribes speech, generates vector embeddings, reranks documents, and manages files and vector stores using the Laravel AI SDK (laravel/ai). Supports structured output, streaming, tools, conversation memory, middleware, queueing, broadcasting, and provider failover. Use when building, editing, updating, debugging, or testing any AI functionality, including agents, LLMs, chatbots, text generation, image generation, audio, transcription, embeddings, RAG, similarity search, vector stores, prompting, structured output, or any AI provider (OpenAI, Anthropic, Gemini, Cohere, Groq, xAI, ElevenLabs, Jina, OpenRouter).
- `developing-with-fortify` — Laravel Fortify headless authentication backend development. Activate when implementing authentication features including login, registration, password reset, email verification, two-factor authentication (2FA/TOTP), profile updates, headless auth, authentication scaffolding, or auth guards in Laravel applications.
- `documentation-automation` — Automates documentation when features are added or modified. Activates when creating Actions, Controllers, Pages, Routes, or Models; when modifying config/fortify.php; or when user mentions docs, documentation, readme.
- `durable-workflow` — Durable Workflow (durable-workflow/workflow, durable-workflow/waterline) and Waterline. Activates when defining workflows or activities, using WorkflowStub, monitoring workflows at /waterline, or when the user mentions durable workflow, Waterline, long-running workflows, sagas, or workflow orchestration.
- `laravel-data-table` — Server-side DataTables with machour/laravel-data-table (Laravel + Inertia + React, TanStack Table). Activates when building or editing data tables, DataTable classes, table columns/filters/sorting, quick views, exports, or when the user mentions DataTable, data table, server-side table, make:data-table.
- `laravel-excel` — Laravel Excel and Filament Excel exports (maatwebsite/excel, pxlrbt/filament-excel). Activates when adding or editing exports, imports, Filament table exports, DataTable exports, or when the user mentions Laravel Excel, Excel export, import, maatwebsite/excel, or filament-excel.
- `pan-product-analytics` — Product analytics with Pan (panphp/pan). Activates when adding or changing tabs, CTAs, nav links, buttons, or key UI that should be tracked for impressions, hovers, and clicks; or when the user mentions analytics, tracking, Pan, data-pan, or product analytics.
- `taylor-otwell-style` — Code PHP and Laravel applications in the style of Taylor Otwell — the creator of Laravel. Use this skill whenever the user asks to write PHP code, Laravel applications, packages,  APIs, services, or any backend code and wants it to follow Laravel conventions, Taylor  Otwell's coding philosophy, or "elegant PHP." Trigger on: Laravel development, PHP package  creation, API design, service classes, Eloquent models, migrations, controllers, middleware, artisan commands, service providers, fluent interfaces, collection pipelines, or any  request mentioning "Laravel-style," "expressive syntax," "Taylor Otwell," or "code like  Laravel." Also trigger when the user wants to refactor messy PHP into clean, idiomatic  Laravel code. Even if the user just says "write this in PHP" — if you can apply Laravel  patterns to make it better, consult this skill.
- `visibility-sharing` — Visibility and cross-organization sharing with HasVisibility. Activates when working with HasVisibility trait, VisibilityEnum, Shareable, VisibilityScope, shareItem policy, or copy-on-write cloning.

## Conventions

- You must follow all existing code conventions used in this application. When creating or editing a file, check sibling files for the correct structure, approach, and naming.
- Use descriptive names for variables and methods. For example, `isRegisteredForDiscounts`, not `discount()`.
- Check for existing components to reuse before writing a new one.

## Verification Scripts

- Do not create verification scripts or tinker when tests cover that functionality and prove they work. Unit and feature tests are more important.

## Application Structure & Architecture

- Stick to existing directory structure; don't create new base folders without approval.
- Do not change the application's dependencies without approval.

## Frontend Bundling

- If the user doesn't see a frontend change reflected in the UI, it could mean they need to run `npm run build`, `npm run dev`, or `composer run dev`. Ask them.

## Documentation Files

- You must only create documentation files if explicitly requested by the user.

## Replies

- Be concise in your explanations - focus on what's important rather than explaining obvious details.

=== boost rules ===

# Laravel Boost

## Tools

- Laravel Boost is an MCP server with tools designed specifically for this application. Prefer Boost tools over manual alternatives like shell commands or file reads.
- Use `database-query` to run read-only queries against the database instead of writing raw SQL in tinker.
- Use `database-schema` to inspect table structure before writing migrations or models.
- Use `get-absolute-url` to resolve the correct scheme, domain, and port for project URLs. Always use this before sharing a URL with the user.
- Use `browser-logs` to read browser logs, errors, and exceptions. Only recent logs are useful, ignore old entries.

## Searching Documentation (IMPORTANT)

- Always use `search-docs` before making code changes. Do not skip this step. It returns version-specific docs based on installed packages automatically.
- Pass a `packages` array to scope results when you know which packages are relevant.
- Use multiple broad, topic-based queries: `['rate limiting', 'routing rate limiting', 'routing']`. Expect the most relevant results first.
- Do not add package names to queries because package info is already shared. Use `test resource table`, not `filament 4 test resource table`.

### Search Syntax

1. Use words for auto-stemmed AND logic: `rate limit` matches both "rate" AND "limit".
2. Use `"quoted phrases"` for exact position matching: `"infinite scroll"` requires adjacent words in order.
3. Combine words and phrases for mixed queries: `middleware "rate limit"`.
4. Use multiple queries for OR logic: `queries=["authentication", "middleware"]`.

## Artisan

- Run Artisan commands directly via the command line (e.g., `php artisan route:list`). Use `php artisan list` to discover available commands and `php artisan [command] --help` to check parameters.
- Inspect routes with `php artisan route:list`. Filter with: `--method=GET`, `--name=users`, `--path=api`, `--except-vendor`, `--only-vendor`.
- Read configuration values using dot notation: `php artisan config:show app.name`, `php artisan config:show database.default`. Or read config files directly from the `config/` directory.
- To check environment variables, read the `.env` file directly.

## Tinker

- Execute PHP in app context for debugging and testing code. Do not create models without user approval, prefer tests with factories instead. Prefer existing Artisan commands over custom tinker code.
- Always use single quotes to prevent shell expansion: `php artisan tinker --execute 'Your::code();'`
  - Double quotes for PHP strings inside: `php artisan tinker --execute 'User::where("active", true)->count();'`

=== php rules ===

# PHP

- Always declare `declare(strict_types=1);` at the top of every `.php` file.
- Always use curly braces for control structures, even for single-line bodies.
- Use PHP 8 constructor property promotion: `public function __construct(public GitHub $github) { }`. Do not leave empty zero-parameter `__construct()` methods unless the constructor is private.
- Use explicit return type declarations and type hints for all method parameters: `function isAccessible(User $user, ?string $path = null): bool`
- Use TitleCase for Enum keys: `FavoritePerson`, `BestLake`, `Monthly`.
- Prefer PHPDoc blocks over inline comments. Only add inline comments for exceptionally complex logic.
- Use array shape type definitions in PHPDoc blocks.

=== inertia-laravel/core rules ===

# Inertia

- Inertia creates fully client-side rendered SPAs without modern SPA complexity, leveraging existing server-side patterns.
- Components live in `resources/js/pages` (unless specified in `vite.config.js`). Use `Inertia::render()` for server-side routing instead of Blade views.
- ALWAYS use `search-docs` tool for version-specific Inertia documentation and updated code examples.
- IMPORTANT: Activate `inertia-react-development` when working with Inertia client-side patterns.

# Inertia v3

- Use all Inertia features from v1, v2, and v3. Check the documentation before making changes to ensure the correct approach.
- New v3 features: standalone HTTP requests (`useHttp` hook), optimistic updates with automatic rollback, layout props (`useLayoutProps` hook), instant visits, simplified SSR via `@inertiajs/vite` plugin, custom exception handling for error pages.
- Carried over from v2: deferred props, infinite scroll, merging props, polling, prefetching, once props, flash data.
- When using deferred props, add an empty state with a pulsing or animated skeleton.
- Axios has been removed. Use the built-in XHR client with interceptors, or install Axios separately if needed.
- `Inertia::lazy()` / `LazyProp` has been removed. Use `Inertia::optional()` instead.
- Prop types (`Inertia::optional()`, `Inertia::defer()`, `Inertia::merge()`) work inside nested arrays with dot-notation paths.
- SSR works automatically in Vite dev mode with `@inertiajs/vite` - no separate Node.js server needed during development.
- Event renames: `invalid` is now `httpException`, `exception` is now `networkError`.
- `router.cancel()` replaced by `router.cancelAll()`.
- The `future` configuration namespace has been removed - all v2 future options are now always enabled.

=== laravel/core rules ===

# Do Things the Laravel Way

- Use `php artisan make:` commands to create new files (i.e. migrations, controllers, models, etc.). You can list available Artisan commands using `php artisan list` and check their parameters with `php artisan [command] --help`.
- If you're creating a generic PHP class, use `php artisan make:class`.
- Pass `--no-interaction` to all Artisan commands to ensure they work without user input. You should also pass the correct `--options` to ensure correct behavior.

### Model Creation

- When creating new models, create useful factories and seeders for them too. Ask the user if they need any other things, using `php artisan make:model --help` to check the available options.

## APIs & Eloquent Resources

- For APIs, default to using Eloquent API Resources and API versioning unless existing API routes do not, then you should follow existing application convention.

## URL Generation

- When generating links to other pages, prefer named routes and the `route()` function.

## Testing

- When creating models for tests, use the factories for the models. Check if the factory has custom states that can be used before manually setting up the model.
- Faker: Use methods such as `$this->faker->word()` or `fake()->randomDigit()`. Follow existing conventions whether to use `$this->faker` or `fake()`.
- When creating tests, make use of `php artisan make:test [options] {name}` to create a feature test, and pass `--unit` to create a unit test. Most tests should be feature tests.

## Vite Error

- If you receive an "Illuminate\Foundation\ViteException: Unable to locate file in Vite manifest" error, you can run `npm run build` or ask the user to run `npm run dev` or `composer run dev`.

## Deployment

- Laravel can be deployed using [Laravel Cloud](https://cloud.laravel.com/), which is the fastest way to deploy and scale production Laravel applications.

=== wayfinder/core rules ===

# Laravel Wayfinder

Use Wayfinder to generate TypeScript functions for Laravel routes. Import from `@/actions/` (controllers) or `@/routes/` (named routes).

=== pint/core rules ===

# Laravel Pint Code Formatter

- If you have modified any PHP files, you must run `vendor/bin/pint --dirty --format agent` before finalizing changes to ensure your code matches the project's expected style.
- Do not run `vendor/bin/pint --test --format agent`, simply run `vendor/bin/pint --format agent` to fix any formatting issues.

=== pest/core rules ===

## Pest

- This project uses Pest for testing. Create tests: `php artisan make:test --pest {name}`.
- Run tests: `php artisan test --compact` or filter: `php artisan test --compact --filter=testName`.
- Do NOT delete tests without approval.

=== inertia-react/core rules ===

# Inertia + React

- IMPORTANT: Activate `inertia-react-development` when working with Inertia React client-side patterns.

=== filament/filament rules ===

## Filament

- Filament is used by this application. Follow the existing conventions for how and where it is implemented.
- Filament is a Server-Driven UI (SDUI) framework for Laravel that lets you define user interfaces in PHP using structured configuration objects. Built on Livewire, Alpine.js, and Tailwind CSS.
- Use the `search-docs` tool for official documentation on Artisan commands, code examples, testing, relationships, and idiomatic practices. If `search-docs` is unavailable, refer to https://filamentphp.com/docs.

### Artisan

- Always use Filament-specific Artisan commands to create files. Find available commands with the `list-artisan-commands` tool, or run `php artisan --help`.
- Always inspect required options before running a command, and always pass `--no-interaction`.

### Patterns

Always use static `make()` methods to initialize components. Most configuration methods accept a `Closure` for dynamic values.

Use `Get $get` to read other form field values for conditional logic:

<code-snippet name="Conditional form field visibility" lang="php">
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Utilities\Get;

Select::make('type')
    ->options(CompanyType::class)
    ->required()
    ->live(),

TextInput::make('company_name')
    ->required()
    ->visible(fn (Get $get): bool => $get('type') === 'business'),

</code-snippet>

Use `state()` with a `Closure` to compute derived column values:

<code-snippet name="Computed table column value" lang="php">
use Filament\Tables\Columns\TextColumn;

TextColumn::make('full_name')
    ->state(fn (User $record): string => "{$record->first_name} {$record->last_name}"),

</code-snippet>

Actions encapsulate a button with an optional modal form and logic:

<code-snippet name="Action with modal form" lang="php">
use Filament\Actions\Action;
use Filament\Forms\Components\TextInput;

Action::make('updateEmail')
    ->schema([
        TextInput::make('email')
            ->email()
            ->required(),
    ])
    ->action(fn (array $data, User $record) => $record->update($data))

</code-snippet>

### Testing

Always authenticate before testing panel functionality. Filament uses Livewire, so use `Livewire::test()` or `livewire()` (available when `pestphp/pest-plugin-livewire` is in `composer.json`):

<code-snippet name="Table test" lang="php">
use function Pest\Livewire\livewire;

livewire(ListUsers::class)
    ->assertCanSeeTableRecords($users)
    ->searchTable($users->first()->name)
    ->assertCanSeeTableRecords($users->take(1))
    ->assertCanNotSeeTableRecords($users->skip(1));

</code-snippet>

<code-snippet name="Create resource test" lang="php">
use function Pest\Laravel\assertDatabaseHas;
use function Pest\Livewire\livewire;

livewire(CreateUser::class)
    ->fillForm([
        'name' => 'Test',
        'email' => 'test@example.com',
    ])
    ->call('create')
    ->assertNotified()
    ->assertRedirect();

assertDatabaseHas(User::class, [
    'name' => 'Test',
    'email' => 'test@example.com',
]);

</code-snippet>

<code-snippet name="Testing validation" lang="php">
use function Pest\Livewire\livewire;

livewire(CreateUser::class)
    ->fillForm([
        'name' => null,
        'email' => 'invalid-email',
    ])
    ->call('create')
    ->assertHasFormErrors([
        'name' => 'required',
        'email' => 'email',
    ])
    ->assertNotNotified();

</code-snippet>

<code-snippet name="Calling actions in pages" lang="php">
use Filament\Actions\DeleteAction;
use function Pest\Livewire\livewire;

livewire(EditUser::class, ['record' => $user->id])
    ->callAction(DeleteAction::class)
    ->assertNotified()
    ->assertRedirect();

</code-snippet>

<code-snippet name="Calling actions in tables" lang="php">
use Filament\Actions\Testing\TestAction;
use function Pest\Livewire\livewire;

livewire(ListUsers::class)
    ->callAction(TestAction::make('promote')->table($user), [
        'role' => 'admin',
    ])
    ->assertNotified();

</code-snippet>

### Correct Namespaces

- Form fields (`TextInput`, `Select`, etc.): `Filament\Forms\Components\`
- Infolist entries (`TextEntry`, `IconEntry`, etc.): `Filament\Infolists\Components\`
- Layout components (`Grid`, `Section`, `Fieldset`, `Tabs`, `Wizard`, etc.): `Filament\Schemas\Components\`
- Schema utilities (`Get`, `Set`, etc.): `Filament\Schemas\Components\Utilities\`
- Actions (`DeleteAction`, `CreateAction`, etc.): `Filament\Actions\`. Never use `Filament\Tables\Actions\`, `Filament\Forms\Actions\`, or any other sub-namespace for actions.
- Icons: `Filament\Support\Icons\Heroicon` enum (e.g., `Heroicon::PencilSquare`)

### Common Mistakes

- **Never assume public file visibility.** File visibility is `private` by default. Always use `->visibility('public')` when public access is needed.
- **Never assume full-width layout.** `Grid`, `Section`, and `Fieldset` do not span all columns by default. Explicitly set column spans when needed.
- **Use correct property types when overriding Page, Resource, and Widget properties.** These properties have union types or changed modifiers that must be preserved:
  - `$navigationIcon`: `protected static string | BackedEnum | null` (not `?string`)
  - `$navigationGroup`: `protected static string | UnitEnum | null` (not `?string`)
  - `$view`: `protected string` (not `protected static string`) on Page and Widget classes

=== prism-php/prism rules ===

## Prism

- Prism is a Laravel package for integrating Large Language Models (LLMs) into applications with a fluent, expressive and eloquent API.
- IMPORTANT: Activate `developing-with-prism` skill when working with Prism features.

=== filament/blueprint rules ===

## Filament Blueprint

You are writing Filament v5 implementation plans. Plans must be specific enough
that an implementing agent can write code without making decisions.

**Start here**: Read
`/vendor/filament/blueprint/resources/markdown/planning/overview.md` for plan format,
required sections, and what to clarify with the user before planning.

</laravel-boost-guidelines>

---

## Project conventions (survives boost:update)

These apply in addition to the Laravel Boost guidelines above. They are kept **after** the `</laravel-boost-guidelines>` block so `php artisan boost:update` does not remove them.

- **Multi-tenancy:** Organizations own content; use `TenantContext`, `SetTenantContext`, `EnsureTenantContext` (`tenant` middleware), and `BelongsToOrganization` trait. Spatie permissions use `organization_id` as team. Domain/subdomain resolution via `ResolveDomainMiddleware` and `organization_domains`. Single-tenant mode: `MULTI_ORGANIZATION_ENABLED=false` hides org UI. See `config/tenancy.php`, docs/developer/backend/billing-and-tenancy.md, docs/developer/backend/single-tenant-mode.md.
- **Visibility & Sharing:** For global/org/shared data and cross-organization sharing, use the `HasVisibility` trait (do not combine with `BelongsToOrganization` on the same model). Models need `organization_id`, `visibility`; optional `cloned_from` for copy-on-write. Share via `Shareable` (view/edit, optional expiry); authorize with `shareItem` (ShareablePolicy). See docs/developer/backend/visibility-sharing.md, `App\Models\VisibilityDemo`.
- **Org permissions:** JSON-driven org permissions in `database/seeders/data/organization-permissions.json`; run `permission:sync` to create and assign. Use `$user->canInOrganization()`, `@canOrg`, etc. See docs/developer/backend/permissions.md.
- **Billing:** laravelcm/laravel-subscriptions + Stripe + Lemon Squeezy (one-time products); `HasCredits` and `HasBilling` traits on Organization (`modules/billing/src/Traits/`); seat-based billing (`BillingSettings`, `SyncSubscriptionSeatsAction`); billing routes under `tenant` middleware. See `config/billing.php`, `modules/billing/src/Http/Controllers/`, docs/developer/backend/billing-and-tenancy.md, docs/developer/backend/lemon-squeezy.md.
- **Full-text search:** Use Laravel Scout; driver Typesense (Herd: `SCOUT_DRIVER=typesense`, `TYPESENSE_API_KEY=LARAVEL-HERD`, `TYPESENSE_HOST=localhost`). Add `Searchable` trait and `toSearchableArray()` (id as string, created_at as UNIX timestamp); define collection schema in `config/scout.php` under `typesense.model-settings`. See docs/developer/backend/scout-typesense.md.
- **Third-party APIs:** use Saloon; add connectors and requests under `App\Http\Integrations\{Name}\` (see docs/developer/backend/saloon.md).
- **Server-side DataTables:** machour/laravel-data-table (installed from fork coding-sunshine/laravel-data-table via VCS). One PHP class per model in `App\DataTables\*` (DTO + table config); Inertia + React UI; run `npx shadcn@latest add ./vendor/machour/laravel-data-table/react/public/r/data-table.json` to install React components. To develop the package in place, use a Composer path repository. See docs/developer/backend/data-table.md.
- **Backups:** spatie/laravel-backup (v10) (config/backup.php, docs/developer/backend/backup.md).
- **Userstamps:** wildside/userstamps for created_by/updated_by (docs/developer/backend/userstamps.md).
- **Product analytics (Pan):** panphp/pan tracks impressions, hovers, and clicks via `data-pan="name"` on HTML elements. Use only letters, numbers, dashes, underscores. Add new names to `AppServiceProvider::configurePan()` allowedAnalytics whitelist. View with `php artisan pan` or in Filament at Analytics → Product Analytics (`/admin/analytics/product`). See docs/developer/backend/pan.md. When adding new tabs, CTAs, or key nav/buttons, add `data-pan` and register the name in the whitelist.
- **Database Mail (email templates):** martinpetricko/laravel-database-mail stores email templates in the DB and sends them when events are dispatched. For new events that should send DB-backed emails: implement `TriggersDatabaseMail` and `CanTriggerDatabaseMail`, define `getName()`, `getDescription()`, `getRecipients()`, and optionally `getAttachments()`; register the event in `config/database-mail.php` under `'events'`. Create templates via seeders or Filament plugin. See docs/developer/backend/database-mail.md.
- **DB-backed settings & config overlay:** spatie/laravel-settings stores runtime config in the DB (26 settings classes in `app/Settings/`). `SettingsOverlayServiceProvider` writes these into `config()` at boot — all `config('...')` consumers read DB values transparently. Per-org overrides stored in `organization_settings` table and applied by `ApplyOrganizationSettings` middleware (after `SetTenantContext`). 7 groups are org-overridable: Billing, Mail, Stripe, Paddle, LemonSqueezy, Prism, AI. To add a new setting: create class in `app/Settings/`, add migration in `database/settings/`, add to `SettingsOverlayServiceProvider::OVERLAY_MAP`, create Filament page. Artisan: `settings:cache`, `settings:clear-cache`. See docs/developer/backend/settings.md, ADR-002.
- **Architecture decisions:** record in docs/architecture/ADRs/ (see README there).
- **Theming, branding & page builder:** App theme via `config/theme.php`, `ThemeSettings`, Filament ManageTheme; org branding via `OrganizationSettingsService::getBranding()`, `BrandingController`, `settings/branding.tsx`; custom pages via Puck (`Modules\PageBuilder\Models\Page`, `Modules\PageBuilder\Http\Controllers\PageController`, `PageViewController`, `puck-config.tsx`, `puck-blocks/`, `Modules\PageBuilder\Services\PageDataSourceRegistry`). See docs/developer/backend/theming-and-page-builder.md.
- **Durable Workflow & Waterline:** durable-workflow/workflow for long-running workflows (sagas, onboarding, AI pipelines); durable-workflow/waterline UI at `/waterline` (admin only). Workflows live in `modules/workflows/src/Workflows/`; service provider at `Modules\Workflows\WorkflowsServiceProvider`. Workflows run on Laravel queues (Horizon). Gate `viewWaterline` same as Horizon (`access admin panel`). See docs/developer/backend/durable-workflow.md.
- **User onboarding:** spatie/laravel-onboard for multi-step onboarding (verify email, complete profile, get started); steps in OnboardingServiceProvider; feature flag `onboarding`; middleware EnsureOnboardingComplete; CompleteOnboardingAction. See docs/developer/backend/onboarding.md.
- **Health checks:** spatie/laravel-health for scheduled checks and notifications (mail by default in `config/health.php`; Slack can be enabled once notifications are compatible with your Slack channel stack); checks registered in HealthServiceProvider; `health:check` scheduled every 5 minutes. See docs/developer/backend/health.md.
- **Rate-limited jobs:** spatie/laravel-rate-limited-job-middleware; throttle queued jobs via job middleware (allow/everySeconds/releaseAfterSeconds); used on ProcessWebhookJob, NotifyUsersOfNewTermsVersion, billing reminders, VerifyOrganizationDomain. See docs/developer/backend/rate-limited-jobs.md.

## Design System
Always read DESIGN.md before making any visual or UI decisions.
All font choices, colors, spacing, and aesthetic direction are defined there.
Do not deviate without explicit user approval.
In QA mode, flag any code that doesn't match DESIGN.md.

### Quick Reference
- **Aesthetic**: Industrial-minimal, dark-first. References: Linear, Raycast, Vercel.
- **Display font**: JetBrains Mono 700 (headings, stats, data). **Body font**: IBM Plex Sans 400/500/600. **Code**: JetBrains Mono 400.
- **Accent**: Muted teal `oklch(0.65 0.14 165)`. Neutrals have cool blue undertone (hue 260).
- **No card shadows** — use background-level differentiation only.
- **Motion**: Minimal-functional. 100ms micro, 200ms transitions, 300ms page-level. No bounce/spring.
- **Accessibility**: WCAG 2.1 AA. Full keyboard nav, reduced-motion respect.

### 5 Design Principles
1. **Precision over decoration** — every element earns its place
2. **Speed is a feature** — skeleton states, optimistic updates, smooth transitions
3. **Dark-first confidence** — design for dark first, adapt for light
4. **Keyboard-first, mouse-friendly** — command palette, shortcuts, focus rings
5. **Progressive disclosure** — show what matters now, reveal complexity on demand
