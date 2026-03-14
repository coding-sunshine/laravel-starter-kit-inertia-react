# Chief: Autonomous Full Build — Fusion CRM Rebuild

Use this document when you run **Chief** (or any autonomous agent) on the **full rebuild-plan folder** so it can build the full software without manual prompts between steps.

---

## SPEED MODE — Active (minimise build time)

These rules **override** any conflicting instruction in this file or in `chief-tasks.md`:

1. **No per-task tests.** Do NOT run `composer test:quick` after each task. Run it **once only** after the final task (Task 20), and only to collect pass/fail counts for the Final Report. Never re-run tests to self-heal — log test failures and move on.
2. **No Visual QA.** Do NOT run the `00-visual-qa-protocol.md` browser automation checks at all. Skip every "Then run Visual QA" instruction in `chief-tasks.md`. Log "Visual QA skipped (speed mode)" per task in the Final Report.
3. **No doc pre-reading.** Do NOT re-read `00-ui-design-system.md`, `00-thesys-conversational-ai.md`, or plan reference docs before each task. You may reference them inline when a specific implementation question arises.
4. **Verify-import commands are kept.** Run `fusion:verify-import-*` commands — they are fast DB row-count checks, not test suites.
5. **Implement and advance.** After each task: run migrations (`php artisan migrate --force`), run any verify-import command, log any errors, then immediately start the next task. Do not wait, retry, or self-heal beyond a single quick fix attempt.

---

## STEP 0 — Pre-flight: Check environment and ask before starting

**Before writing any code or running any command**, Chief must complete this pre-flight checklist. Check what already exists in the workspace (`.env`, `config/`, `composer.json`) and ask the human for anything missing. Do NOT proceed to Task 1 until all required items are confirmed.

### Pre-flight checks (run in order)

**1. Workspace check**
- [ ] Confirm the current working directory is the **Laravel Starter Kit** repo (not the legacy fusioncrmv3 repo). Check: does `composer.json` contain `nunomaduro/laravel-starter-kit-inertia-react` or equivalent, and does `artisan` exist?
- [ ] Confirm `rebuild-plan/` exists in the workspace root (this file should be at `rebuild-plan/CHIEF.md`).
- [ ] If either fails: **STOP and ask** — “What is the correct kit directory path?”

**2. PHP, Composer, Bun**
- [ ] Run `php --version` → must be 8.5+. If not: **STOP and ask** for the PHP upgrade path.
- [ ] Run `composer --version` → must exist.
- [ ] Run `bun --version` → must exist.
- [ ] If any tool is missing: **STOP and ask** — “Please install [tool] and confirm.”

**3. .env file and required credentials**

Check if `.env` exists. If it does, read the relevant values. Then ask the human to confirm or fill in any that are blank/example values. Present **all missing items as a single grouped question** — do not ask one by one.

**Required — build cannot proceed without these:**

| Variable(s) | Used by | Ask if missing |
|---|---|---|
| `DB_CONNECTION=pgsql`, `DB_HOST`, `DB_PORT`, `DB_DATABASE`, `DB_USERNAME`, `DB_PASSWORD` | All steps | “What are your PostgreSQL connection details?” |
| `DB_LEGACY_HOST`, `DB_LEGACY_PORT`, `DB_LEGACY_DATABASE`, `DB_LEGACY_USERNAME`, `DB_LEGACY_PASSWORD` | Import Tasks 2–6 (Steps 3,1,2,4,5) | Pre-fill from v3 `.env`: `127.0.0.1` / `3306` / `fv3` / `root` / `` (empty password). Chief sets these automatically for local dev — only ask if the human confirms their MySQL differs from these defaults. |
| `OPENAI_API_KEY` **or** `OPENROUTER_API_KEY` | AI agents + Prism (Step 6+) | “Which AI provider — OpenAI or OpenRouter? Please provide the API key.” |
| `THESYS_API_KEY` | Thesys C1 generative UI — every AI agent response (Steps 1,3,4,5,6+) | “Please provide your Thesys API key. All AI chat responses render via C1; without this they fall back to plain text.” |
| `DATA_TABLE_AI_MODEL` | DataTable NLQ + Visualize (Steps 1,3,4,5,7) | “Which model for DataTable AI? (e.g. `openai/gpt-4o-mini` for OpenRouter or `gpt-4o-mini` for OpenAI)” |
| `COHERE_API_KEY` | RAG memory reranking — `rerank-english-v3.0` (Step 6+) | “Do you have a Cohere API key? Used for AI agent memory recall reranking.” |
| `REVERB_APP_ID`, `REVERB_APP_KEY`, `REVERB_APP_SECRET`, `REVERB_HOST`, `REVERB_PORT`, `REVERB_SCHEME` | Reverb WebSockets — real-time events (Step 6+) | Pre-fill from Herd local: `1001` / `laravel-herd` / `secret` / `reverb.herd.test` / `443` / `https`. Chief sets these automatically — only ask if not using Herd. |
| `APP_NAME` | All | Default: `”Fusion CRM”` — Chief sets this if blank |
| `APP_KEY` | All | Chief generates via `php artisan key:generate` |
| `APP_ENV=local`, `APP_DEBUG=true`, `LOG_LEVEL=debug` | All | Dev defaults — Chief sets these automatically |
| `QUEUE_CONNECTION=sync` | All steps with jobs/imports | **Critical for dev** — without this, import jobs and AI jobs queue silently and never run. Chief sets this automatically. |
| `MAIL_MAILER=log` | Steps 5, 23 (email triggers) | Emails go to `storage/logs/laravel.log` in dev — not sent. Chief sets this automatically. |
| `LEGACY_APP_PATH` | Import schema reference (Tasks 2–6) | Pre-fill: `/Users/apple/Code/clients/piab/fusioncrmv3`. Chief sets this automatically — only ask if the path doesn't exist on disk. |

**Optional — Chief notes if missing, continues without blocking:**

| Variable(s) | Used by | Behaviour if missing |
|---|---|---|
| `WP_PROVISIONER_URL` | WordPress site provisioning (Step 21) | Sites created at `stage=2` (Initializing) but no WP instance spun up — no crash, provisioning deferred. Same behaviour as v3 production (not set there either). |
| `WP_DOMAIN_REAL_ESTATE`, `WP_DOMAIN_WEALTH_CREATION`, `WP_DOMAIN_FINANCE` | WP site domains (Step 21) | Pre-fill from v3: `mypropertyenquiry.com.au` for all three. Chief sets these automatically — no input needed. |
| `WP_IP_REAL_ESTATE`, `WP_IP_WEALTH_CREATION`, `WP_IP_FINANCE` | WP server IPs (Step 21) | Pre-fill from v3: `54.253.51.191` for all three. Chief sets these automatically — no input needed. |
| `STRIPE_KEY`, `STRIPE_SECRET`, `STRIPE_WEBHOOK_SECRET` | Subscription billing — Stripe driver (Step 23) | **Deferred.** Step 23 stubs the billing layer; Stripe is not wired until credentials are provided. Skip without blocking. |
| `EWAY_API_KEY`, `EWAY_API_PASSWORD`, `EWAY_ENDPOINT` | eWAY — reservation deposits (Step 4) + eWAY billing driver (Step 23) | **Deferred.** eWAY integration skipped entirely; reservation deposit flow is stubbed. Add credentials when ready to activate payment processing. |
| `BILLING_GATEWAY` | Payment gateway selector (Step 23) | **Deferred** with Stripe/eWAY above. Stub only. |
| `TYPESENSE_HOST`, `TYPESENSE_PORT`, `TYPESENSE_API_KEY`, `SCOUT_DRIVER=typesense` | Full-text search — Contact, Project, Lot (Steps 1,3,4) | Scout index jobs queue silently; search falls back to DB LIKE queries. Add before Step 1 for best results or defer to production. |
| `TINYURL_API_KEY` | Short links on campaign sites (Step 5) | Short links skipped; full UUID URL used instead |
| `VAPI_API_KEY` | Voice AI / Vapi integration (Step 16) | Skip voice features in Step 16; log as deferred |
| `XERO_CLIENT_ID`, `XERO_CLIENT_SECRET` | Xero OAuth — Step 22 | Step 22 stubs the Xero connector; OAuth flow won't work until set. Add before Task 17. |
| `RESEMBLE_API_KEY`, `RESEMBLE_PROJECT_UUID` | Voice cloning — Resemble.ai (Step 24 R&D) | Resemble.ai deliverable skipped in Step 24; log as deferred |
| `SENTRY_LARAVEL_DSN` | Error tracking | Production only; skip in dev |
| `BOOST_ENABLED` | Dev performance tool | Default: `true` in dev |
| `VITE_REVERB_APP_KEY`, `VITE_REVERB_HOST`, `VITE_REVERB_PORT`, `VITE_REVERB_SCHEME` | Frontend WebSocket client | Pre-fill from Herd local: `laravel-herd` / `reverb.herd.test` / `443` / `https`. Chief sets these automatically to match the Reverb server values above. |

> **Key rule:** If any **Required** variable is blank or missing after reading `.env`, add it to the single grouped question presented to the human. Do not start Task 1 until all Required items are confirmed. For Optional items — note them in the pre-flight summary and continue.

**If any Required item is blank or unset:** Present all missing items as a single list and ask the human to provide them before proceeding. Do not start Task 1 until all required items are confirmed.

**4. Legacy app path**
- [ ] Check if `LEGACY_APP_PATH` is set in `.env`. If not, default to `/Users/apple/Code/clients/piab/fusioncrmv3` and verify it exists on disk.
- [ ] If that path doesn't exist: ask — “Where is the Fusion CRM v3 directory? Import steps need it for MySQL schema reference and data. (Full path, e.g. /Users/apple/Code/clients/piab/fusioncrmv3)”

**5. PostgreSQL connectivity**
- [ ] Once DB credentials are confirmed, run `php artisan db:show --counts` (or `php artisan migrate:status` if app is partially set up) to verify PostgreSQL is reachable.
- [ ] If connection fails: **STOP and ask** — “PostgreSQL connection failed with [error]. Please check credentials or ensure PostgreSQL is running.”

**6. composer test:quick check**
- [ ] Check `composer.json` for a `test:quick` script. If missing, add it now (before Task 1): `”test:quick”: “pest --no-coverage”` in the `scripts` section, and run `composer dump-autoload`.

**7. Summarise and ask for go-ahead**

Once all checks pass and the human has provided any missing Required values, present a single summary:

```
Pre-flight complete. Here is what was confirmed:

ENVIRONMENT
✅ Workspace: [kit directory]
✅ PHP [version], Composer [version], Bun [version]
✅ PostgreSQL: connected to [database]
✅ Legacy MySQL: [DB_LEGACY_DATABASE @ DB_LEGACY_HOST]
✅ Legacy app path: [LEGACY_APP_PATH]
✅ composer test:quick: defined
✅ Dev mode: APP_ENV=local, APP_DEBUG=true, QUEUE_CONNECTION=sync, MAIL_MAILER=log

AI / REAL-TIME
✅ AI provider: [OPENAI_API_KEY / OPENROUTER_API_KEY]
✅ THESYS_API_KEY: set
✅ DATA_TABLE_AI_MODEL: [model]
✅ COHERE_API_KEY: set (RAG reranking)
✅ REVERB: pre-filled (Herd local — reverb.herd.test:443)
✅ VITE_REVERB_*: pre-filled to match

PAYMENTS (deferred)
⚠️  STRIPE / EWAY / BILLING_GATEWAY: not required — payment integration stubbed until credentials provided

INTEGRATIONS
✅ WP_PROVISIONER_URL: [url or “⚠️ not set — WP site provisioning deferred”]
✅ XERO_CLIENT_ID / XERO_CLIENT_SECRET: [set or “⚠️ not set — Xero OAuth deferred until Task 17”]

OPTIONAL / DEFERRED
⚠️  TYPESENSE: [set / not set — search falls back to DB LIKE if not set]
⚠️  VAPI_API_KEY: [set / not set — voice features skipped in Task 11 if not set]
⚠️  RESEMBLE_API_KEY: [set / not set — voice cloning skipped in Task 19 if not set]
⚠️  SENTRY_LARAVEL_DSN: [set / not set — production only, OK to defer]
⚠️  TINYURL_API_KEY: [set / not set — short links skipped if not set]

Ready to start Task 1 (Step 0: Bootstrap).
After this confirmation I will build all 20 tasks autonomously without interruption.
The next time I ask you anything will be the Final Report at the end.
Shall I proceed?
```

Wait for the human to say “yes” or “proceed”. This is **human gate #1 — the only question before the Final Report**. After this confirmation Chief runs all tasks to completion without stopping.

---

## Workspace and paths

- **Workspace:** The **Laravel Starter Kit (Inertia + React)** repo. The rebuild-plan must live **inside** the kit at `./rebuild-plan` (so this file is at `rebuild-plan/CHIEF.md`). All implementation happens in the kit repo; there is no separate “fusion-crm-new” directory.
- **Kit repo:** Current workspace root = the app being built. Use paths relative to workspace (e.g. `rebuild-plan/`, `database/migrations/`, `app/Models/`).
- **Legacy app (reference/import source):** For import steps (Tasks 2–6), the legacy Fusion CRM v3 codebase is needed for schema reference and MySQL data. Use `LEGACY_APP_PATH` env var; fallback to `../fusioncrmv3`. Do not implement new code in the legacy repo; use it only for DB schema and import source.

---

## How to run the full build

1. **Pre-flight first** (see above): Check environment, collect missing credentials, get human go-ahead. Then proceed.
2. **Before Task 1 — read these plan docs in full (once, before any code):**
   - `rebuild-plan/00-schedule-a-feature-blueprint.md` — the canonical feature list; every deliverable must map to a feature here. Use it to cross-check scope decisions throughout the build.
   - `rebuild-plan/00-database-design.md` — schema reference; all models and migrations follow this.
   - `rebuild-plan/00-roles-permissions.md` — complete role/permission matrix, contact type enum, org-scoping rules, seeder spec. **Read before creating any policy, gate, or seeder.**
   - `rebuild-plan/00-ai-credits-system.md` — AI credit schema, `AiCreditService`, BYOK, per-plan defaults, Filament controls. **Read before wiring any AI feature.**
   - `rebuild-plan/09-coverage-audit.md` — 100% coverage checklist; verify nothing is skipped.
   - `rebuild-plan/12-ai-execution-and-human-in-the-loop.md` — execution rules and read order.
   - `rebuild-plan/00-component-map.md` — Inertia page paths, shadcn component usage per page, React component structure, logo path, TypeScript types

3. **Task list:** Execute tasks in order from **`rebuild-plan/chief-tasks.md`**. Each task = one step (Step 0, 3, 1, 2, 4, 5, 6, 7, 14…24, optional 25).
4. **Per task:**
   - Read the step file and any referenced plan docs (see `rebuild-plan/12-ai-execution-and-human-in-the-loop.md` §2 Read order).
   - **For Tasks 2–6 (import steps)**: Read **`rebuild-plan/00-import-command-templates.md`** first. It defines the base import command class, idempotency rules, `mysql_legacy` connection config, the lead_id→contact_id map pattern, verification command structure, and quick reference CLI. All `fusion:import-*` commands must follow this template.
   - Implement the step in the **kit repo** (workspace). Prefer **Filament Blueprint** (YAML + **composer setup-blueprint** or `php artisan blueprint:build`) for Filament resources; see 00-kit-package-alignment.md. For steps that add models (1–6), run **php artisan models:audit** after implementation.
   - Run the step’s verification (e.g. `fusion:verify-import-*`) if the step has one, and run **`composer test:quick`**.
   - Run the **Visual QA protocol** (`rebuild-plan/00-visual-qa-protocol.md`) for any Inertia pages added in this task.
   - If verification, tests, and visual QA all pass (or any failures are self-healed): **mark the task done and proceed to the next task immediately.**

5. **Autonomous mode (2 human gates only):** After pre-flight, Chief runs all 20 tasks without stopping for human review. The only stops are:
   - A **safety risk** requiring explicit authorization (see “Mid-build stops” below).
   - After **all tasks complete**: present the Final Report and ask for human sign-off.
   - **Ignore** “Human-in-the-loop” and “STOP after this step” in each step file — those are for manual-guided runs only.

6. **Self-healing (never stop on test/verification failure unless safety risk):**
   - If `composer test:quick` fails: read the failure, fix the code, re-run — up to **3 attempts**. If still failing after 3 attempts: log the failure, continue to the next task, include in Final Report.
   - If `fusion:verify-import-*` fails with a count mismatch: check for data mapping issues (null foreign keys, missing lead_id→contact_id maps), fix the import command, re-run — up to 3 attempts. If still failing: log the gap, continue.
   - If a Visual QA check fails: follow the self-healing loop in `00-visual-qa-protocol.md` (fix → reload → re-check, max 3 attempts). If unresolved: screenshot + log + continue.
   - **Goal: zero mid-build stops.** Everything goes into the Final Report for the human to review.

---

## Development mode — Chief has full permissions

This is a **local development build**. Chief must not hesitate or ask permission for any of the following:

### Environment (set in .env during Step 0)

```env
APP_ENV=local
APP_DEBUG=true
LOG_LEVEL=debug
QUEUE_CONNECTION=sync          # No worker needed — jobs run inline
MAIL_MAILER=log                # Emails written to storage/logs/laravel.log, not sent
CACHE_STORE=database           # Or array — no Redis needed in dev
SESSION_DRIVER=database
DEBUGBAR_ENABLED=true          # Laravel Debugbar on in dev
TELESCOPE_ENABLED=true         # Laravel Telescope if installed
```

Setting `QUEUE_CONNECTION=sync` is critical — import jobs, AI jobs, and email jobs all run inline without needing `php artisan queue:work`. Do not leave jobs queued silently with no worker running.

### Commands Chief may run freely — no permission needed

| Command | When |
|---|---|
| `php artisan migrate:fresh --seed` | Any time the DB is in a broken state or a clean rebuild is faster |
| `php artisan migrate --force` | Any step's new migrations |
| `php artisan db:seed --class=AnySeeder` | Any time seed data is needed |
| `php artisan tinker` (inline eval) | To inspect data or debug mapping issues |
| `php artisan optimize:clear` | After any config/route/view change |
| `php artisan cache:clear && php artisan config:clear && php artisan route:clear` | Any time caching causes stale behaviour |
| `php artisan queue:flush` | If any queued jobs stall |
| `php artisan horizon:terminate` | If Horizon is running and needs restart |
| `php artisan scout:flush "App\Models\Contact"` | Reset Scout index |
| `php artisan scout:import "App\Models\Contact"` | Re-index Scout |
| `bun install` / `bun add [package]` | Install any JS package needed |
| `bun run build` / `bun run dev` | Rebuild assets at any time |
| `composer require [package]` | Install any Composer package needed |
| `composer dump-autoload` | After adding new classes |
| `npx shadcn@latest add [component]` | Install any shadcn component needed |

### Package installation — Chief installs without asking

If any required package is missing from `composer.json` or `package.json`, Chief installs it immediately using `composer require` or `bun add`. Chief does not ask the human first. The only exception is a package that requires a paid licence key — in that case log it and skip.

### Database operations in dev

- `migrate:fresh --seed` is safe and encouraged when a step introduces schema changes that conflict with existing data.
- Chief may freely run `DB::statement(...)` via tinker or a migration to inspect or fix data.
- Chief may add `--force` to any artisan command that prompts for production confirmation.
- Chief may modify `database/seeders/` files at any time to add or fix seed data.

### File system — Chief may freely

- Read, write, or edit any file in the workspace (app/, config/, resources/, routes/, database/, tests/).
- Delete generated files (compiled views, cached routes, stale migrations) if they conflict.
- Copy files from `LEGACY_APP_PATH` (read-only legacy source) into the new app.
- Create new directories anywhere under the workspace root.

### What this means for self-healing

When a test or verification fails, Chief should **immediately try the most direct fix** — including dropping and recreating tables, re-running seeders, reinstalling packages, or clearing all caches — without asking. The 3-attempt self-healing limit still applies, but each attempt can be a different and more aggressive approach.

---

## Mid-build stops (safety-only — Chief must not stop for routine issues)

After pre-flight, Chief pauses **only** for genuine safety risks:

1. **Destructive action not in the plan**: Dropping a table not listed in any migration plan, deleting production data, overwriting imported data without a backup. Ask once, clearly. Otherwise make the conservative choice (add column, don't drop).
2. **Credential / key genuinely missing and unresolvable**: e.g. an API key needed RIGHT NOW and not in `.env` and has no fallback (not just a warning). Ask for it once, grouped with any other missing items.

**Chief does NOT stop for:**
- Test failures — self-heal (max 3 attempts), log, continue
- Verification count mismatches — self-heal, log, continue
- Visual QA failures — self-heal (max 3 attempts per check), log, continue
- Ambiguous column mappings — make a reasonable decision based on `00-database-design.md` + `10-mysql-usage-and-skip-guide.md`, document the decision in a code comment, continue
- Missing optional env vars — note it, continue without that feature

**Decision rule for ambiguous choices**: If the plan has any guidance, follow it. If not, choose the most conservative/reversible option, add a `// CHIEF: assumed [X] because [Y]` comment in the code, and log it for the Final Report.

---

## Final Gate (Human Interaction #2)

After **all 20 tasks** complete (or the last applicable task), Chief presents the Final Report and asks for human sign-off. The report follows `00-visual-qa-protocol.md § Final Report Format` and includes:

- ✅/⚠️/❌ status for each task
- Screenshots of all key pages (from `storage/app/qa/`)
- All self-healed failures and fixes applied
- Any unresolved failures (rare — require human review)
- `composer test:quick` summary
- Decisions made autonomously (column mappings, stubs, etc.)

**Prompt to human:** "All tasks complete. Please review the report and screenshots above. Approve to go live, or list items to fix."

---

## Prerequisites

- **Always:** PHP 8.5+, Composer, Bun, PostgreSQL. Step 0 sets up env, migrations, and seed.
- **Kit as app base:** The workspace must be the **Laravel Starter Kit** (Option A or C from Step 0), not a bare `laravel new` app, so kit commands (e.g. `php artisan make:model:full`) and Composer scripts exist.
- **`composer test:quick`:** Pre-flight adds this if missing before Task 1.
- **Import steps (Tasks 2–6):** Legacy MySQL must be reachable via `mysql_legacy` connection. Pre-flight confirms this.

---

## Entry point for Chief

**Single instruction you can give Chief:**
“Read `rebuild-plan/CHIEF.md` first. Run the pre-flight checklist, collect any missing credentials, and ask me to confirm before starting — this is the only time you ask before the final report. Then build the Fusion CRM rebuild by following every task in `rebuild-plan/chief-tasks.md` in order. After each task: run the step’s verification (if any), run `composer test:quick`, and run the Visual QA protocol in `rebuild-plan/00-visual-qa-protocol.md` for any pages built. Self-heal any failures (max 3 attempts each) and continue — do not stop for human review. When all tasks are done, present the Final Report and ask for sign-off.”

For more detail on read order, verification commands, and kit paths, use **`rebuild-plan/12-ai-execution-and-human-in-the-loop.md`** (and the **Chief / autonomous mode** subsection there).
