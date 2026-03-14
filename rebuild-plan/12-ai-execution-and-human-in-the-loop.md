# Instructions for AI: Step-by-Step Execution and Human-in-the-Loop

**Purpose:** If you are an AI agent given the **rebuild-plan** folder and the **Laravel Starter Kit (Inertia + React)** repo, this document tells you exactly how to understand and execute the plan, and **when to stop for human review**. By default the human must approve at the end of **every step** before you proceed. **Chief / autonomous mode** (see §3.1) skips human approval between steps when running the full build from **CHIEF.md** and **chief-tasks.md**.

---

## 1. What You Have

- **Rebuild plan** (this folder): `00-database-design.md` through `11-verification-per-step.md`, plus step files `01-step-0-bootstrap.md` … `08-step-7-reporting-and-dashboards.md`, **Phase 1 / Phase 2** step files `14-step-8-phase-1-parity.md` through `24-step-18-phase-2-rd-special.md`, and optional **25-sync-two-way-30min.md** (two-way sync every 30 min). This folder is **copied into** the starter kit directory (e.g. `laravel-starter-kit-inertia-react/rebuild-plan/`) so the plan and the app live in one workspace.
- **Starter kit repo = new app:** Laravel 12, Inertia + React, PostgreSQL, Filament, Spatie, DataTables, Prism, Laravel AI SDK, pgvector. Path: `/Users/apple/Code/cogneiss/Products/laravel-starter-kit-inertia-react`. Fusion CRM is built **in this directory** (in-place); no separate “fusion-crm-new” folder. Copy rebuild-plan into this repo, then run Step 0 (env, migrate, build, seed) here.
- **Legacy app (reference only):** Fusion CRM v3 at `/Users/apple/Code/clients/piab/fusioncrmv3` — MySQL schema and migrations for mapping; **do not** implement Fusion in this repo.
- **Legacy schema for imports:** For import command implementation, use the legacy app’s migrations or DB schema (e.g. `fusioncrmv3/database/migrations`) to resolve exact MySQL column names.

---

## 2. Read Order (Before Implementing Any Step)

1. **README.md** — Overview, execution order, design principles.
2. **00-database-design.md** — New schema (Contact, no lead_id everywhere), table list, migration mapping old→new.
3. **09-coverage-audit.md** — Every table → step, every import command, every AI enhancement (nothing implicit).
4. **10-mysql-usage-and-skip-guide.md** — Which tables to skip (empty), optional (low use), or must-import; baseline row counts from Herd MySQL.
5. **11-verification-per-step.md** — Expected row counts and verification commands per step.
6. **This file (12)** — How to execute and where to stop for human.
6b. **00-thesys-conversational-ai.md** — How to use Thesys C1 API for generative UI responses in agents and DataTables. Read before Step 6 and any step adding AI-visible responses.

**Before implementing any Inertia page or DataTable:** Read **00-ui-design-system.md** in full. Every CRM page must follow: brand tokens (§1–2), design principles (§5), reference apps (§6), layout patterns (§7), typography (§8), spacing (§9), interactive states (§10), animation tokens (§11), form conventions (§12), DataTable conventions (§13), icon rules (§14), and the **anti-patterns hard no list (§15)**. Do **not** use default shadcn neutral styles. Run the pre-delivery checklist (§19) on every page before marking a step done.

Then, for **the step you are about to implement** (e.g. Step 3 after Step 0, then Step 1, then Step 2 — see execution order in README):

7. **0N-step-N-….md** — Full content: Goal, Starter Kit References, Deliverables, DB Design, Data Import, AI Enhancements, Verification, **Human-in-the-loop**.
8. **Starter kit** — Read the referenced docs and code paths (e.g. `docs/developer/backend/database/seeders.md`, `docs/developer/backend/actions/README.md`, `database/migrations/` patterns).

---

## 3. Execution Rules

- **One step at a time.** Implement only the step you were asked to do (e.g. “Implement Step 1”).
- **Starter kit is source of truth.** Follow its directory layout, conventions (actions, DataTables, Filament, seeders), and docs. Do not invent patterns that conflict with the kit.
- **New app = kit repo (in-place).** The kit directory is the new app. Step 0 bootstraps it (env, migrate, build, seed, mysql_legacy); Steps 1–7 and 14–24 are implemented **in the kit repo**, not in the legacy Fusion CRM repo. Rebuild-plan is copied into the kit (e.g. `rebuild-plan/`) so one workspace has both plan and code.
- **After completing a step:** Run verification (if applicable) and **composer test:quick**. Then:
  - **Default (human-in-the-loop):** **STOP and hand back to the human.** Do **not** start the next step until the human has approved.
  - **Chief / autonomous mode:** If you are running from **CHIEF.md** and **chief-tasks.md** (full autonomous build), do **not** stop for human approval. If verification and `composer test:quick` both pass, **proceed to the next task** in chief-tasks.md immediately. If either fails, report the failure and stop; do not advance.
- **Batch mode (optional):** If the user explicitly requests multiple steps with a single review (e.g. "Implement Steps 1–3 with one review at the end"), complete all requested steps then STOP once and present all step checklists together; otherwise one step at a time.
- **Phased go-live:** The plan supports **superadmin-first** rollout: after Steps 0–7 (and optionally 14), the human can go live for superadmin only (projects, materials, chatbots, contacts, reports). **Subscriber-specific** features (subscriber login, own-leads scope, org owner experience, reservation form, Step 23 signup) can be **deferred** to a second phase. See **README.md** § Phased go-live.

### 3.1 Chief / autonomous mode

When the agent is run with the **full rebuild-plan folder** for an **autonomous full build** (e.g. via [Chief](https://chiefloop.com/) using **rebuild-plan/CHIEF.md** and **rebuild-plan/chief-tasks.md**):

- **Workspace** = Laravel Starter Kit repo; rebuild-plan at `./rebuild-plan`. Legacy app path = env (e.g. `LEGACY_APP_PATH`) or documented fallback; see **CHIEF.md**.
- **Task list** = **chief-tasks.md** (one task per step in order: 0, 3, 1, 2, 4, 5, 6, 7, 14…24, optional 25).
- **Human interaction gates:** Only **2 human interactions** in the entire run — (1) the pre-flight credential check at the very start, and (2) the final sign-off report after all tasks complete. Do **not** stop between tasks for any other reason unless a genuine safety risk arises (see CHIEF.md).
- **Per task (SPEED MODE):** Implement in the kit repo, run `php artisan migrate --force`, run the step’s verify-import command (if any). Do NOT run `composer test:quick` per task. Do NOT run Visual QA. Log any migration or verify-import errors and advance immediately to the next task. Run `composer test:quick` once after Task 20 for the Final Report only.
- **Mid-build stops (safety only):** Chief stops mid-build ONLY for: a destructive action not described in the plan (e.g. dropping a production table), or genuinely missing credentials that cannot be resolved from `.env` or plan docs. It does **NOT** stop for: test failures, verification mismatches, visual QA failures, build errors, or ambiguous decisions.
- **Prerequisites for import steps (Tasks 2–6):** Legacy MySQL must be reachable and **mysql_legacy** configured in the kit; Step 0 sets this up.

---

## 4. Human-in-the-Loop: End of Every Step

At the **end of each step**, you must:

1. **Output a short summary** for the human:
   - What was implemented (migrations, models, commands, pages, etc.).
   - What was run (e.g. `php artisan migrate`, `fusion:import-contacts`, `fusion:verify-import-contacts`).
   - Verification result: **PASS** or **FAIL** and, if FAIL, what failed (e.g. row count mismatch).
   - **Tests**: Run **composer test:quick** (or composer test) at the end of each step and report pass/fail in the human checklist. Minimum: feature tests for import commands (dry-run where applicable); Pest tests for Filament resources (CRUD) as steps are implemented.
   - Any blockers or decisions the human needs to make (e.g. “Empty table X was skipped as per 10-mysql-usage-and-skip-guide.”).

2. **List the human checklist** (from the step’s “Human-in-the-loop” section):
   - Copy the checklist from the step file so the human can tick items and confirm.

3. **Explicitly state:** “**STOP — Human review required.** Do not proceed to Step N+1 until the human has verified the above and approved.”

4. **Wait for human approval** before implementing the next step. If the human says “proceed to Step N+1”, then (and only then) read the next step file and implement it, then again STOP for human review.

---

## 5. Per-Step Human Checklist (Summary)

Each step file contains a **“Human-in-the-loop (end of step)”** section. Use that section as the authoritative checklist. Below is a quick reference:

| Step | Human must verify before proceeding |
|------|-------------------------------------|
| **0** | New app runs; env:validate and app:health pass; migrations and seed ran; **composer test:quick** pass; env (PostgreSQL) correct; tenancy set via Filament Settings or config. |
| **3** | Projects, lots, developers, suburbs, project_updates, flyers, etc. imported; counts match baseline; verification PASS. |
| **1** | Contacts (with contact_origin: property default; saas_product for v4) + contact_emails/contact_phones exist; import ran; verification PASS; lead_id→contact_id map persisted; CRM roles seeded. |
| **2** | users.contact_id added; every user that had lead_id has contact_id set; each v3 subscriber has own org (owner_id = user); verification PASS. |
| **4** | Reservations, enquiries, searches, sales, commissions imported; all contact FKs resolve; verification PASS. |
| **5** | Tasks, relationships, partners, notes, comments, addresses, statusables, campaign_websites, resources, etc. imported; verification PASS. |
| **6** | AI agents and/or AI config imported; verification PASS; at least one agent or prompt command usable. |
| **7** | Dashboard and reports load; KPIs consistent with imported data. |
| **14–24** | See each step file's "Human-in-the-loop (end of step)" section (Phase 1 parity then Phase 2 features). |
| **25 (optional)** | Two-way sync: scheduler runs every 30 min; MySQL→PostgreSQL and PostgreSQL→MySQL verified; conflict rule documented. |

---

## 6. What “100% Covered” Means

- **Tables:** Every legacy table is assigned to a step (or “skip”) in **09-coverage-audit.md** and **10-mysql-usage-and-skip-guide.md**.
- **Imports:** Every import command and its source/target tables are listed in **09-coverage-audit.md** §2 and in the step files.
- **AI:** Every step that has an AI enhancement has it listed in the step file and in **09-coverage-audit.md** §3. **Additional AI-native ideas** (duplicate detection, similar projects, RAG, email drafts, note summarization, etc.) are in **13-ai-native-features-by-section.md** — use that when implementing so each section has considered AI where appropriate.
- **Verification:** Every step that imports data has expected row counts and a verification command or checklist in **11-verification-per-step.md** and in the step file.

If something is missing from the plan, add it to the appropriate step or to the coverage audit and then implement; do not assume.

---

## 7. Quick Reference: Starter Kit Paths

- Migrations: `database/migrations/`
- Models: `app/Models/`
- Actions: `app/Actions/` — single-action classes, `handle()`, see `docs/developer/backend/actions/`
- Controllers: `app/Http/Controllers/` — see `docs/developer/backend/controllers/`
- DataTables: `app/DataTables/` — see `docs/developer/backend/data-table.md`
- Inertia pages: `resources/js/pages/` — use **laravel/wayfinder** for type-safe route generation in React (e.g. `route(ContactController.show, { id })` instead of string URLs). See 00-kit-package-alignment.md.
- Filament: `app/Providers/Filament/`, see `docs/developer/backend/filament.md`
- Seeders: `database/seeders/` (Essential, Development, Production), see `docs/developer/backend/database/seeders.md`
- Prism (AI): `docs/developer/backend/prism.md`
- Laravel AI SDK: `docs/developer/backend/ai-sdk.md`, agents in `app/Ai/Agents/`
- pgvector: `docs/developer/backend/pgvector.md`
- Single-tenant: `docs/developer/backend/single-tenant-mode.md`

### 7.1 Kit automation commands (quick reference)

Use these so the new CRM aligns with the kit and an AI (or human) can follow the plan step-by-step with human-in-the-loop at the end of each step.

| Command | Purpose |
|---------|---------|
| `php artisan make:model:full ModelName --migration --factory --seed --category=essential\|development` | Model + migration + factory + seeder + LogsActivity; add `--all` for controller, policy, form requests. |
| `php artisan seeders:sync` | Create missing seeders for all models; use `--dry-run` to preview. |
| `php artisan seeds:from-prose "description" --model=ModelName` | Generate seed spec from natural language (optional). |
| `php artisan make:filament-resource Model --generate --view` | Filament resource with form, infolist, table, pages. |
| `php artisan make:data-table Model` | DataTable class; add `--export --route` for export and route. |
| `php artisan make:data UserData` | DTO class; see `docs/developer/backend/search-and-data.md`. |

Each step file (02–06) includes a **Suggested implementation order** and **Models to generate** so you can run these generators first, then add plan-specific columns and import commands.

### 7.2 Batch generation (optional)

Example one-liners for Step 1 and Step 3 (run from new app root):

```bash
# Step 1
for m in Contact Source Company; do php artisan make:model:full $m --migration --factory --seed --category=essential; done

# Step 3 (subset)
for m in Project Lot Developer Projecttype State Suburb; do php artisan make:model:full $m --migration --factory --seed --category=development; done
```

---

## 8. Per-step doc read list

Before implementing a step, read the relevant kit docs:

| When | Read (starter kit paths) |
|------|---------------------------|
| **Steps with new models** (1–5) | `docs/developer/backend/database/seeders.md`, `database/README.md` |
| **Steps with Filament** | `docs/developer/backend/filament.md` |
| **Steps with DataTables** | `docs/developer/backend/data-table.md` (backend); frontend DataTable docs in `docs/developer/frontend/` |
| **Steps with actions** | `docs/developer/backend/actions/README.md` |
| **Steps with import commands** | This plan’s **09-coverage-audit.md** §2 and **Import commands contract** (below); **10-mysql-usage-and-skip-guide.md** |
| **Steps with verification** | **11-verification-per-step.md** (machine-parseable output, exit codes) |

Reference this in the **Read order** (§2): after opening the step file, read the kit paths listed above for that step type.

---

## 9. Import commands contract (data import standards)

All `fusion:import-*` commands must:

- Use the **config connection** for legacy data (e.g. `mysql_legacy` from `config/database.php`), not hardcoded credentials.
- Support **`--dry-run`** (no writes; log what would be imported) and **`--chunk=N`** (or document that chunk size is configurable) so large imports can be tuned.
- Optionally share a **base class or trait** for: resolving connection, dry-run, and chunking, so behavior is consistent and scriptable.
- Be **idempotent** where possible (e.g. updateOrCreate keyed by legacy id or business key) so re-running after a fix does not create duplicates.

Document any command that does not support `--dry-run` or `--chunk` in the step file and in **09-coverage-audit.md** §2.

---

## 10. Verification command output standard

Each `fusion:verify-import-*` command must produce **machine-parseable** output (e.g. one line such as `contacts: 9678, contact_emails: 12000, PASS` or JSON) and use **exit code 0 for PASS**, **non-zero for FAIL**, so verification is scriptable and CI-friendly. See **11-verification-per-step.md** for per-step expected counts and examples.
