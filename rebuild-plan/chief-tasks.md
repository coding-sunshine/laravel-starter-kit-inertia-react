# Chief Task List — Fusion CRM Rebuild

Use this file with [Chief](https://chiefloop.com/) to run the full rebuild **autonomously**: one task per step, in order. The workspace must be the **Laravel Starter Kit** repo with this **rebuild-plan** folder at `./rebuild-plan`. See **CHIEF.md** for entry point and path conventions.

**Before starting Task 1:** Run the pre-flight checklist in **CHIEF.md §"STEP 0 — Pre-flight"**. Check workspace, PHP/Composer/Bun, PostgreSQL, legacy MySQL credentials, ALL required API keys (THESYS_API_KEY, OPENAI/OPENROUTER, DATA_TABLE_AI_MODEL, COHERE, REVERB, STRIPE, EWAY, WP_PROVISIONER_URL), legacy app path,. Also create `storage/app/qa/` for screenshots. Collect all missing Required items in a **single grouped question** — present the pre-flight summary and ask the human to confirm before proceeding. **This is human gate #1 — the only question asked before the Final Report.**

**Before Task 1 also read these plan docs in full (once only):**
- `rebuild-plan/00-schedule-a-feature-blueprint.md` — canonical feature list and step mapping
- `rebuild-plan/00-database-design.md` — schema reference
- `rebuild-plan/00-roles-permissions.md` — **complete role/permission matrix, contact type enum, org-scoping rules, seeder spec. Read before creating any policy or seeder.**
- `rebuild-plan/00-ai-credits-system.md` — **AI credit schema, AiCreditService, BYOK, per-plan defaults. Read before wiring any AI feature.**
- `rebuild-plan/09-coverage-audit.md` — 100% coverage checklist
- `rebuild-plan/12-ai-execution-and-human-in-the-loop.md` — execution rules
- `rebuild-plan/00-component-map.md` — Inertia page paths, shadcn component usage per page, React component structure, TypeScript types, logo path, and the prototype→component mapping table. HTML prototypes in `rebuild-plan/ui-prototypes/` are visual design references; use this map as the implementation guide.

**SPEED MODE — see `CHIEF.md § SPEED MODE` (overrides everything below):** Do NOT run `composer test:quick` per task. Do NOT run Visual QA. After each task: run `php artisan migrate --force` + the step’s verify-import command (if any), log any errors, advance immediately. Run `composer test:quick` once after Task 20 for the Final Report only.

---

## Task 1 — Step 0: Bootstrap

**Step file:** `rebuild-plan/01-step-0-bootstrap.md`

**First:** Read `rebuild-plan/00-ui-design-system.md` in full (all sections, currently 20). Apply brand tokens to `resources/css/app.css` (§2, including fusion-dark vars), create the `fusion` (and `fusion-dark`) theme preset in config (e.g. `config/theme.php`), set `THEME_PRESET=fusion` and `THEME_FONT=geist-sans` in .env. All subsequent Inertia pages and DataTables must follow: design principles (§5), typography (§8), spacing (§9), interactive states (§10), animation (§11), form conventions (§12), DataTable conventions (§13), icon rules (§14), the anti-patterns hard no list (§15), pass the pre-delivery checklist (§19), and apply the additional UX patterns in §21 (AI states, credits widget, onboarding banner, plan selection, filter URL persistence, toasts, Kanban drag feedback, Sheet mobile behaviour, error pages, empty states).

Then implement Step 0 (Bootstrap) per the step file. Read: `rebuild-plan/README.md`, `rebuild-plan/12-ai-execution-and-human-in-the-loop.md`, then `rebuild-plan/01-step-0-bootstrap.md`. Ensure env (PostgreSQL), migrations, seed, and `mysql_legacy` config (for later import steps) are in place. Run `php artisan env:validate`, `php artisan app:health`,.

---

## Task 2 — Step 3: Projects and lots

**Step file:** `rebuild-plan/04-step-3-projects-and-lots.md`

**Import reference:** Read **`rebuild-plan/00-import-command-templates.md`** before writing any `fusion:import-*` command. It defines the base command class (handle, mapRow, upsertRow), the `mysql_legacy` DB connection config, the lead_id→contact_id map pattern, idempotency rules, and error handling. All import commands follow this template.

**Flyers/websites reference:** Read **`rebuild-plan/00-websites-puck-builder.md`** before implementing the flyer PDF generator and flyer template system in this step. It covers the full flyer spec (spatie/laravel-pdf, template/lot/project bindings) and applies to Step 3 flyers.

Implement Step 3 (Projects and lots) per the step file. Read the step file and any referenced plan docs (00-database-design, 09-coverage-audit, 10-mysql-usage-and-skip-guide, 11-verification-per-step). Run `fusion:import-projects-lots` (or dry-run as specified), then `fusion:verify-import-projects-lots`.

---

## Task 3 — Step 1: Contacts and roles

**Step file:** `rebuild-plan/02-step-1-contacts-and-roles.md`

Implement Step 1 (Contacts and roles) per the step file. Run `fusion:import-contacts`, then `fusion:verify-import-contacts`.

---

## Task 4 — Step 2: Users and contact link

**Step file:** `rebuild-plan/03-step-2-users-and-contact-link.md`

Implement Step 2 (Users and contact link) per the step file. Run `fusion:import-users` (or link pass).

---

## Task 5 — Step 4: Reservations, sales, commissions

**Step file:** `rebuild-plan/05-step-4-reservations-sales-commissions.md`

Implement Step 4 per the step file. Run `fusion:import-reservations-sales`, then `fusion:verify-import-reservations-sales`.

---

## Task 6 — Step 5: Tasks, relationships, marketing

**Step file:** `rebuild-plan/06-step-5-tasks-relationships-marketing.md`

**Campaign websites reference:** Read **`rebuild-plan/00-websites-puck-builder.md`** before implementing campaign websites in this step. It covers the Puck visual page builder (`@measured/puck`), campaign site structure, live CRM data components (ProjectHero, LotGrid, EnquiryForm), and the full campaign site/WordPress/PHP site spec for Steps 5 and 21.

Implement Step 5 per the step file. Run the import command(s), verification,.

---

## Task 7 — Step 6: AI-native features

**Step file:** `rebuild-plan/07-step-6-ai-native-features.md`

**First:** Read `rebuild-plan/00-thesys-conversational-ai.md` in full. All AI agent responses shown to users must be rendered via Thesys C1 generative UI (`@thesys/client` React SDK), not plain text. Register custom CRM components (ContactCard, PropertyCard, PipelineFunnel, EmailCompose, CommissionTable, TaskChecklist) before wiring agents.

Implement Step 6 (AI agents, RAG, Thesys C1 UI, lead scoring, buyer-lot matching, prompt commands, import) per the step file. Run any `fusion:verify-import-*` for this step.

---

## Task 8 — Step 7: Reporting and dashboards

**Step file:** `rebuild-plan/08-step-7-reporting-and-dashboards.md`

Implement Step 7 (Reporting and dashboards) per the step file.

---

## Task 9 — Step 14: Phase 1 parity

**Step file:** `rebuild-plan/14-step-8-phase-1-parity.md`

Implement Phase 1 parity per the step file. Run verification (if any).  Task done when step checklist is done and migration runs.

---

## Task 10 — Step 15: Phase 2 — AI lead generation

**Step file:** `rebuild-plan/15-step-9-phase-2-ai-lead-generation.md`

Implement Step 15 per the step file. Run verification (if any). Task done when step checklist is done and migration runs.

---

## Task 11 — Step 16: Phase 2 — AI core and voice

**Step file:** `rebuild-plan/16-step-10-phase-2-ai-core-and-voice.md`

Implement Step 16 per the step file. **Note:** VAPI_API_KEY needed for voice features; if not set, skip voice integration and log it. Run verification (if any). Task done when step checklist is done and migration runs.

---

## Task 12 — Step 17: Phase 2 — Property, builder & push portal

**Step file:** `rebuild-plan/17-step-11-phase-2-property-builder-push-portal.md`

**Builder reference:** Read **`rebuild-plan/00-websites-puck-builder.md`** before implementing the Puck visual page builder and push portal in this step. It covers the full Puck builder spec (`@measured/puck`), live CRM data component registration, push portal architecture, and WordPress/PHP site provisioning — all of which are central deliverables for Step 17.

Implement Step 17 per the step file. Run verification (if any). Task done when step checklist is done and migration runs.

---

## Task 13 — Step 18: Phase 2 — CRM analytics

**Step file:** `rebuild-plan/18-step-12-phase-2-crm-analytics.md`

Implement Step 18 per the step file. Run verification (if any). Task done when step checklist is done and migration runs.

---

## Task 14 — Step 19: Phase 2 — Marketing and content

**Step file:** `rebuild-plan/19-step-13-phase-2-marketing-content.md`

Implement Step 19 per the step file. Run verification (if any). Task done when step checklist is done and migration runs.

---

## Task 15 — Step 20: Phase 2 — Deal tracker

**Step file:** `rebuild-plan/20-step-14-phase-2-deal-tracker.md`

Implement Step 20 per the step file. Run verification (if any). Task done when step checklist is done and migration runs.

---

## Task 16 — Step 21: Phase 2 — Websites and API

**Step file:** `rebuild-plan/21-step-15-phase-2-websites-api.md`

Implement Step 21 per the step file. Run verification (if any). Task done when step checklist is done and migration runs.

---

## Task 17 — Step 22: Phase 2 — Xero integration

**Step file:** `rebuild-plan/22-step-16-phase-2-xero-integration.md`

Implement Step 22 per the step file. Run verification (if any). Task done when step checklist is done and migration runs.

---

## Task 18 — Step 23: Phase 2 — Signup and onboarding

**Step file:** `rebuild-plan/23-step-17-phase-2-signup-onboarding.md`

**Payment note (deferred):** Payment processing (Stripe, eWAY) is deferred — credentials not yet available. Implement the `BillingGatewayContract` interface and stub both `StripeBillingDriver` and `EwayBillingDriver` (return success without charging). The signup/onboarding flow, org creation, and plan assignment must work end-to-end; only the actual charge is stubbed. Org is auto-created by `SignupController::provision()` — subscriber never sees a create-org form. `OrganizationPolicy::create()` (set in Task 1) enforces this. Activate real payment drivers when credentials are provided later.

Run verification (if any). Task done when step checklist is done and migration runs.

---

## Task 19 — Step 24: Phase 2 — R&D and special

**Step file:** `rebuild-plan/24-step-18-phase-2-rd-special.md`

Implement Step 24 per the step file. Run verification (if any). Task done when step checklist is done and migration runs.

---

## Task 20 — Two-way sync infrastructure (write scripts; enable if coexistence required)

**Step file:** `rebuild-plan/25-sync-two-way-30min.md`

**Always implement the sync scripts** — write `fusion:sync-mysql-to-postgres` and `fusion:sync-postgres-to-mysql` commands, the scheduler entry (every 30 min), and conflict resolution rules per the step file. The scripts must be production-ready even if not yet activated.

**Enabling the sync is optional:** If the legacy MySQL and new PostgreSQL must coexist (e.g. during a phased rollout), enable the scheduler. If the cutover is complete (all traffic on PostgreSQL), leave the scheduler disabled — but the scripts remain in the codebase for safety.

Run verify the sync commands are importable (dry-run). Task done when scripts exist, and a dry-run log entry confirms both directions work.

---

## Final Gate — Human Sign-off (Interaction #2)

After the last applicable task, compile and present the **Final Report** following `rebuild-plan/00-visual-qa-protocol.md § Final Report Format`:

1. ✅/⚠️/❌ status per task (verification + tests + visual QA)
2. All screenshots from `storage/app/qa/` listed with status
3. All self-healed failures and the fix applied
4. Any unresolved failures with screenshot paths
5. All autonomous decisions made (column mappings, stubs, etc.)
6. `composer test:quick` final summary

Present to human with: **"All tasks complete. Please review the report and screenshots above. Approve to go live, or list items to fix."**

This is the **only** other human interaction after pre-flight.
