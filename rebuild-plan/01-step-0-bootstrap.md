# Step 0: Bootstrap from Starter Kit

## Goal

Create the new Fusion CRM application from the Laravel Starter Kit (Inertia + React) with Laravel 12, PostgreSQL, and optional pgvector. No data import in this step.

**Requires:** PHP 8.5+ (kit README recommends 8.4+), Composer, Bun.

## Starter Kit References

- **Repo**: `/Users/apple/Code/cogneiss/Products/laravel-starter-kit-inertia-react`
- **README**: Getting Started, Composer setup, `composer dev`, `composer test`
- **Docs**: `docs/developer/backend/single-tenant-mode.md`, `docs/developer/backend/pgvector.md`, `docs/developer/README.md`
- **Database**: `database/migrations/` (users, organizations, organization_user, permissions, activity_log, media, agent_conversations, contact_submissions, vector extension, embedding_demos)
- **Config**: `.env.example`, `config/database.php`, `config/tenancy.php`, `config/feature-flags.php`
- **Install & health**: `php artisan app:install` (CLI TUI), web installer at `/install`, express install `POST /install/express` (presets: `internal`, `saas`, `ai_first`); `php artisan env:validate`, `php artisan app:health`; see `docs/developer/backend/controllers/InstallController.md`, `docs/developer/backend/controllers/healthcontroller.md`
- **Kit package alignment**: See **00-kit-package-alignment.md** for Pennant features, Scout, Sentry, Backup, and other package refs.

## Deliverables

1. **New project**
   - **Option A (in-place — rebuild-plan in kit):** Use the **starter kit directory as the new app**. Copy the **rebuild-plan** folder into the kit, e.g. `cp -r /path/to/rebuild-plan /Users/apple/Code/cogneiss/Products/laravel-starter-kit-inertia-react/rebuild-plan`. Then work **in the kit directory** for all steps: set `.env`, run migrations, add Fusion migrations and code there. Single workspace: kit + plan in one repo. Continue from step 2 below.
   - **Option B (from Packagist):** Create a separate app with `composer create-project nunomaduro/laravel-starter-kit-inertia-react fusion-crm-new --prefer-dist`, then copy rebuild-plan into it if desired.
   - **Option C (copy kit to new folder):** Copy the kit to a new folder (e.g. `fusion-crm-new`), copy rebuild-plan into it, then in that folder run `composer install` and continue from step 2.
   - Do **not** use a bare `laravel new` app. The new app **must** be the kit (Option A, B, or C) so kit commands (e.g. `php artisan make:model:full`, Filament/DataTable generators) and scripts (e.g. `composer test:quick`) are available. Chief and Steps 1, 3, 4, 5 depend on these.
   - Do **not** implement Fusion CRM in the legacy Fusion v3 repo (`fusioncrmv3`); the new app is either the kit repo (Option A) or a copy of the kit (Options B/C).

2. **Environment**
   - Copy `.env.example` to `.env`, **or** run `php artisan app:install` (interactive TUI), **or** use the **web installer** at `/install` (or **express install** `POST /install/express` with body e.g. `{"preset":"internal"}` for single-tenant). After any of these, add the **`mysql_legacy`** connection and Fusion-specific env vars manually.
   - Set `DB_CONNECTION=pgsql`, `DB_HOST`, `DB_PORT`, `DB_DATABASE`, `DB_USERNAME`, `DB_PASSWORD` for PostgreSQL.
   - Set `APP_NAME` (e.g. "Fusion CRM").
   - **Development mode** — set these immediately in `.env` so Chief can run freely:
     ```env
     APP_ENV=local
     APP_DEBUG=true
     LOG_LEVEL=debug
     QUEUE_CONNECTION=sync
     MAIL_MAILER=log
     CACHE_STORE=database
     SESSION_DRIVER=database
     DEBUGBAR_ENABLED=true
     ```
     `QUEUE_CONNECTION=sync` is required — without it, import jobs, AI jobs, and mail jobs queue silently and never execute (no worker running in dev).
   - **DataTable AI (Steps 1, 3, 4, 5, 7):** **DATA_TABLE_AI_MODEL** (e.g. `openai/gpt-4o-mini` for OpenRouter or `gpt-4o-mini` for OpenAI); **THESYS_API_KEY** (required — all AI agent responses must render via Thesys C1 generative UI; without it, agent responses appear as plain text). See 00-kit-package-alignment.md § DataTable HasAi.
   - **AI providers (Step 6+):** Set **OPENROUTER_API_KEY** (for Prism + OpenRouter — DeepSeek R1 default for ad-hoc generation) and/or **OPENAI_API_KEY** (for Laravel AI SDK embeddings — `text-embedding-3-small`, 1536 dims). Set **COHERE_API_KEY** (for `rerank-english-v3.0` — used by all CRM agent memory recall). Set these now so Step 6 has them ready. The kit has Prism (`prism-php/prism`) and Laravel AI SDK pre-installed; only env vars are needed.
   - **Reverb (WebSockets, Step 6+):** Kit ships with `laravel/reverb`. Run `php artisan reverb:install` if the kit does not already have a `config/reverb.php`. Set **REVERB_APP_ID**, **REVERB_APP_KEY**, **REVERB_APP_SECRET** in `.env`. Also set the Vite frontend vars: **VITE_REVERB_APP_KEY**, **VITE_REVERB_HOST** (default: `localhost`), **VITE_REVERB_PORT** (default: `8080`). Required for Step 6 broadcast events (ContactCreated, TaskDue, AgentResponse, etc.).
   - **eWAY (Steps 4, 3-SPR):** **EWAY_API_KEY**, **EWAY_API_PASSWORD**, **EWAY_ENDPOINT** (default: `https://api.sandbox.ewaypayments.com` for dev; `https://api.ewaypayments.com` for prod). Used by Saloon `EwayIntegration` for reservation deposits and SPR payments. Kit has Saloon installed; only env vars needed.
   - **TinyURL (Step 21):** **TINYURL_API_KEY** — for generating short URLs on campaign website save. Free tier available at tinyurl.com.
   - **Scout / Typesense (Steps 1, 3, 4):** **SCOUT_DRIVER=typesense**, **TYPESENSE_HOST**, **TYPESENSE_PORT=8108**, **TYPESENSE_PROTOCOL=http**, **TYPESENSE_API_KEY**. If self-hosted, run Typesense on port 8108; if cloud, use typesense.io credentials. Set now so Steps 1, 3, 4 can use `php artisan scout:import` immediately.
   - **Dev:** **BOOST_ENABLED=true** (laravel/boost; `config/boost.php`). Run **boost:update** post-install if needed.
   - **Production (optional):** **ANALYTICS_PROPERTY_ID** (spatie/laravel-analytics).
   - **Theming:** Apply brand tokens from **00-ui-design-system.md** §2 to **resources/css/app.css** (light and dark variables); create the **fusion** preset in **config/theme.php**; set **THEME_PRESET=fusion** and **THEME_FONT=geist-sans** in .env.
   - **Tenancy:** The kit does **not** use an `.env` key for tenancy. After the app is installed, set multi-org on/off in **Filament Settings → Tenancy**. For single-tenant, you can set `'enabled' => false` in `config/tenancy.php`, or use the web/express installer **"Internal tool"** preset (sets single-org on the Tenancy step). See `docs/developer/backend/single-tenant-mode.md`.
   - Optionally enable pgvector: ensure PostgreSQL has the `vector` extension (kit migration `2022_08_03_000000_create_vector_extension.php` runs it when driver is pgsql).

3. **Run kit migrations**
   - `php artisan key:generate` (if not already done).
   - `php artisan migrate` — creates users, organizations, organization_user, permissions, activity_log, media, contact_submissions, agent_conversations, embedding_demos (pgvector), etc.

4. **Frontend build**
   - `bun install` and `bun run build` (or run `composer setup` after setting `.env` for PostgreSQL). Required so `composer dev` and the app UI work.

5. **Seed baseline**
   - `php artisan seed:environment` — seeds roles/permissions (RolesAndPermissionsSeeder) and any Essential/Development seeders so the app is usable.
   - **AI seed generation (optional):** `php artisan seeds:generate-ai` (AI-generated realistic seed data); `php artisan seeds:from-prose "description"` — useful for dev/demo data (contacts, projects, lots) without manual factories. See 00-kit-package-alignment.md.

6. **Verify**
   - Run `php artisan env:validate` to check required env vars (e.g. `APP_KEY`, `DB_*`); use `--production` to warn on file/sync drivers in production.
   - Run `php artisan app:health` to verify database, cache, queue, mail, etc.
   - **Ensure `composer test:quick` exists.** The plan and Chief run `composer test:quick` after every step. If the kit’s `composer.json` does not define a `test:quick` script, add one (e.g. `"test:quick": "pest --no-coverage"` or `pest --filter fast`). Then run `composer test` or `composer test:quick` so it passes.
   - Login/register and dashboard load; organization switcher is visible only when tenancy is enabled (Filament Settings → Tenancy).

7. **Before Step 3 or Step 1 — MySQL connection for imports**
   - All import commands (from Step 3 and Step 1 onwards) read from the legacy MySQL database. Before running any import, add a second database connection **in the kit** (e.g. **`mysql_legacy`** in `config/database.php`) pointing at your Herd MySQL replica (or wherever the Fusion v3 MySQL data lives). Use the same credentials as your current Fusion v3 `.env` MySQL vars or a dedicated set (e.g. `DB_LEGACY_HOST`, `DB_LEGACY_DATABASE`, etc.). The step files refer to this as `mysql_legacy` when calling `DB::connection('mysql_legacy')` in import commands. If you do not add it in Step 0, add it before running `fusion:import-projects-lots` (Step 3) or `fusion:import-contacts` (Step 1).

8. **CRM Pennant Feature classes (see 00-kit-package-alignment.md)**

   Create ALL CRM Feature classes in **App\Features\** before starting any step. Register each in **config/feature-flags.php** (inertia_features + route_feature_map). Step 23 maps plan tiers to these features.

   **Full feature flag list** (create all 14 classes in Step 0):

   | Class | Purpose | Step used |
   |---|---|---|
   | `AiToolsFeature` | AI agents, prompt commands | Step 6 |
   | `PropertyAccessFeature` | Property portal (projects, lots) | Step 3 |
   | `CampaignWebsitesFeature` | Campaign website builder | Step 5/21 |
   | `WordPressSitesFeature` | WordPress site provisioning | Step 21 |
   | `PhpSitesFeature` | PHP site management | Step 21 |
   | `EwayPaymentsFeature` | Reservation eWAY deposit | Step 4 |
   | `ApiAccessFeature` | API Keys management (Sanctum) | Step 2 |
   | `AiBotsCustomFeature` | Custom bot creation in Bot In A Box | Step 5 |
   | `FeaturedProjectsFeature` | Featured Projects widget on dashboard | Step 3 |
   | `SprFeature` | Special Property Request ($55 paid service) | Step 3 |
   | `BotInABoxFeature` | Bot In A Box access (all bots) | Step 5 |
   | `AdvancedReportingFeature` | Advanced reports (Mail Status, Network Activity, Same Device) | Step 7 |
   | `MapViewFeature` | Map view on Projects page (Leaflet) | Step 3 |
   | `RealtimeUpdatesFeature` | Reverb WebSocket listeners on frontend | Step 6+ |

9. **Scout/Typesense (production)**
   - Add Scout/Typesense env vars to env checklist if using full-text search in production (e.g. TYPESENSE_HOST, TYPESENSE_API_KEY). Steps 1, 3, 4 add Searchable to Contact, Project, Lot, Sale.

10. **Filament Blueprint (preferred scaffolding)**
   - Kit has **filament/blueprint: ^2.1.2**. Define Filament resources in YAML; run **composer setup-blueprint** (or `php artisan blueprint:build`) to scaffold PHP. Prefer this over hand-running `make:filament-resource` for faster builds. See 00-kit-package-alignment.md.

11. **models:audit (per step)**
   - **php artisan models:audit** checks models for missing conventions (userstamps, activity log, fillable). Run at the end of every step that adds models (Steps 1, 2, 3, 4, 5, 6) to verify CRM models. See 00-kit-package-alignment.md.

12. **UI design system and branding (00-ui-design-system.md)**
   - Read **rebuild-plan/00-ui-design-system.md**. Apply brand tokens to **resources/css/app.css** (Fusion primary #f28036, warm backgrounds, blue-gray text); create the **fusion** (and **fusion-dark**) theme preset in the kit’s theme config (e.g. `config/theme.php`); set THEME_PRESET and THEME_FONT in .env. All subsequent Inertia pages and DataTables must follow the design principles there (data-first density, orange primary, warm neutrals, left-anchored layout, status as dots, dark mode parity).
   - **Logo migration:** Copy the Fusion CRM logo SVG from the legacy app (e.g. `{LEGACY_APP_PATH}/public/assets/campaign_theme/images/fusion-offer.svg` or nearest branding asset) into the new app’s **public/images/** and set it as the org logo in **Filament → Settings → Branding**. See 00-ui-design-system.md §8.

13. **Package verification (before Step 3)**

   Verify these packages are installed/present. If missing, install now:

   ```bash
   # PHP packages (verify in composer.json, install if absent)
   composer show spatie/laravel-pdf           # Required Step 3 flyer PDF generation
   composer show spatie/laravel-activitylog   # Required Step 7 Network Activity report
   # If missing: composer require spatie/laravel-pdf spatie/laravel-activitylog

   # Publish activity log migration if not already run:
   php artisan vendor:publish --provider="Spatie\Activitylog\ActivitylogServiceProvider" --tag="activitylog-migrations"

   # JS packages (verify in package.json, add if absent)
   # Required for Step 3 Map View:
   bun add leaflet react-leaflet @types/leaflet

   # Required for Step 7 Same Device Detection:
   bun add @fingerprintjs/fingerprintjs

   # Required for Step 21 Landing Page Editor (verify first):
   # bun add @ckeditor/ckeditor5-react @ckeditor/ckeditor5-build-classic
   ```

   Also verify `spatie/laravel-pdf` config is published: `php artisan vendor:publish --tag=pdf-config`

14. **OrganizationPolicy — subscriber cannot create or delete orgs (required)**

    The kit ships with an open "Create Organisation" flow. In Fusion CRM every subscriber **already owns exactly one org** (created by the system at import or signup). Subscribers must not be able to create additional orgs or delete their org. Lock this down in Step 0 so it is never accidentally left open.

    ```php
    // app/Policies/OrganizationPolicy.php
    public function create(User $user): bool
    {
        // Only superadmin can create organisations manually
        // (subscribers get one auto-created at import / signup)
        return $user->hasRole('superadmin');
    }

    public function delete(User $user, Organization $organization): bool
    {
        // Only superadmin can delete an organisation
        return $user->hasRole('superadmin');
    }

    public function update(User $user, Organization $organization): bool
    {
        // Org owner (subscriber) can update their own org profile (name, logo, etc.)
        // Superadmin can update any org
        return $user->hasRole('superadmin')
            || $organization->owner_id === $user->id;
    }

    public function view(User $user, Organization $organization): bool
    {
        return $user->hasRole('superadmin')
            || $organization->owner_id === $user->id
            || $organization->users->contains($user);
    }
    ```

    **Also hide the "Create Organisation" UI from the kit:**
    - Find the kit's Inertia page or Filament action that renders a "New Organisation" button.
    - Gate it with `@can('create', App\Models\Organization::class)` (Blade) or `usePage().props.can.create_organization` (Inertia/React). Only superadmin will see it.
    - Subscribers see their org name/profile as read/update only — no create or delete affordance.

    **Org switcher:** The kit may show an org switcher in the sidebar (for users in multiple orgs). Subscribers are in exactly one org — the switcher should be hidden or show the single org name as a static label for subscriber role. Superadmin sees full switcher across all orgs.

    **Summary of what each role can do with organisations:**

    | Action | Subscriber | Admin | Superadmin |
    |---|---|---|---|
    | Create a new org | ❌ (system only) | ❌ (system only) | ✅ |
    | Delete their org | ❌ | ❌ | ✅ |
    | Update own org profile (name, logo) | ✅ | ✅ | ✅ |
    | View own org | ✅ | ✅ | ✅ (all orgs) |
    | Invite team members to own org | ✅ | ✅ | ✅ |
    | See other orgs | ❌ | ❌ | ✅ |

15. **Production checklist (optional for launch)**
   - **Sentry**: Add SENTRY_LARAVEL_DSN to .env for production; kit has sentry/sentry-laravel.
   - **Backup**: Configure **spatie/laravel-backup** (`config/backup.php`) with S3 or chosen destination for production.
   - **Response cache**: **spatie/laravel-responsecache** — configure for public project/lot listing pages (Step 3/21) for performance.
   - **Horizon**: Queue dashboard at **/horizon** — monitor queue workers during heavy import steps (e.g. 153K project_updates, 180 mail jobs); kit installs it; `app:health` verifies it.
   - **CSP**: **spatie/laravel-csp** — Content Security Policy headers for production security hardening; configure alongside Sentry/Backup.

## DB Design (this step)

- No new CRM tables. Use only the starter kit’s existing schema.

## Data Import

- **None.** Step 0 is bootstrap only.

## AI Enhancements

- None required in this step. However, **set all AI env vars now** (OPENROUTER_API_KEY, OPENAI_API_KEY, COHERE_API_KEY, THESYS_API_KEY, REVERB_APP_KEY) so Steps 6–7 and Phase 2 steps can use them without interruption. The kit already has Prism, Laravel AI SDK, and Reverb installed; no composer installs needed for these — just env vars and `php artisan reverb:install` if config not yet present.

## Verification (verifiable results)

- No data import. Verify: `php artisan env:validate` (exit 0), `php artisan app:health` (exit 0), `php artisan migrate:status` (all Ran), `php artisan seed:environment` (exit 0), `composer test` (pass). See **11-verification-per-step.md** § Step 0.

## Human-in-the-loop (end of step)

**STOP after this step. Do not proceed to Step 3 until the human has completed the checklist below.**

Human must:
- [ ] Confirm the app runs in the kit directory (e.g. `composer dev`, open in browser).
- [ ] Confirm `.env` has correct PostgreSQL; tenancy set in Filament Settings → Tenancy (or in `config/tenancy.php` if overridden).
- [ ] Confirm `php artisan env:validate` and `php artisan app:health` pass (kit-recommended checks).
- [ ] Confirm `php artisan migrate:status` shows all migrations Ran.
- [ ] Confirm `php artisan seed:environment` ran without error.
- [ ] Confirm `composer test:quick` exists in `composer.json` and passes.
- [ ] Confirm `mysql_legacy` (or equivalent) is added in `config/database.php` for imports, or note that it will be added before first import.
- [ ] Confirm all 14 Pennant feature flag classes created in `App\Features\` and registered in `config/feature-flags.php`.
- [ ] Confirm env vars set: OPENROUTER_API_KEY, OPENAI_API_KEY, COHERE_API_KEY, THESYS_API_KEY, DATA_TABLE_AI_MODEL, REVERB_APP_ID/KEY/SECRET, SCOUT_DRIVER, TYPESENSE_*, EWAY_API_KEY, EWAY_API_PASSWORD, EWAY_ENDPOINT, TINYURL_API_KEY.
- [ ] Confirm JS packages installed: `leaflet`, `react-leaflet`, `@types/leaflet`, `@fingerprintjs/fingerprintjs`.
- [ ] Confirm PHP packages present: `spatie/laravel-pdf`, `spatie/laravel-activitylog` (migrations published).
- [ ] Approve proceeding to Step 3 (projects and lots).

## Acceptance Criteria

- [ ] New Laravel 12 app runs from starter kit with PostgreSQL.
- [ ] `.env` has PostgreSQL; tenancy configured (Filament Settings or config).
- [ ] `php artisan env:validate` and `php artisan app:health` pass.
- [ ] All kit migrations run successfully.
- [ ] `php artisan seed:environment` runs without error.
- [ ] Test suite passes.
- [ ] App is ready for Step 3 (projects and lots).
