# Fusion CRM Rebuild Plan

This folder contains the **database design** and **step-by-step implementation plan** for rebuilding Fusion CRM using the **Laravel Starter Kit (Inertia + React)** as the only base. The new app uses **Laravel 12**, **PostgreSQL** (with optional pgvector), and an **AI-native** approach.

**Prerequisites:** PHP 8.5+, Composer, Bun, PostgreSQL, and access to legacy MySQL (for imports from Step 3 onward).

## Contents

| Document | Description |
|----------|-------------|
| [00-roles-permissions.md](./00-roles-permissions.md) | **Roles & permissions**: Complete role list (superadmin → referral_partner), full permission catalogue, role→permission matrix, contact type enum (lead/client/partner/…), org-scoping rules, and seeder spec. Read before Step 0. |
| [00-ai-credits-system.md](./00-ai-credits-system.md) | **AI Credits system**: Per-plan credit allocation, per-user limits, BYOK (bring your own key), usage tracking, credit costs per action, DB schema, `AiCreditService`, Filament superadmin controls, and scheduler. Read before Step 0. |
| [00-database-design.md](./00-database-design.md) | New PostgreSQL schema: Contact (person) entity, table-by-table design, and **migration mapping** from old MySQL (every lead_id → contact_id) to new schema. Starter kit alignment and package usage. |
| [00-payment-gateway-decision.md](./00-payment-gateway-decision.md) | **Decided:** Stripe (kit billing) for subscriptions; **eWAY + Saloon** for reservation/deposit fees; no parallel custom billing tables in Step 23. |
| [00-ui-design-system.md](./00-ui-design-system.md) | **UI design system:** Brand tokens (#f28036 primary, warm #f3f1ee), theme preset (fusion), layout/density principles, reference apps. Apply in Step 0; every Inertia page and DataTable must follow. Avoids generic shadcn gray. |
| [00-kit-package-alignment.md](./00-kit-package-alignment.md) | **Kit package alignment:** State machines, Pennant features, Scout/Typesense, laravel-database-mail, MCP tools, soft-cascade, Reverb, referral, Sentry/Backup, laravel-money, test:quick, and other package refs so implementation follows the kit. |
| [00-import-command-templates.md](./00-import-command-templates.md) | **Import command templates:** Base Artisan command class (handle, mapRow, upsertRow, dry-run, chunking, error handling), lead_id→contact_id map pattern, `mysql_legacy` DB config, idempotency rules, per-command notes, verification command structure, and quick reference CLI. **Read before writing any `fusion:import-*` command.** |
| [01-step-0-bootstrap.md](./01-step-0-bootstrap.md) | Bootstrap from starter kit: new repo, Laravel 12, PostgreSQL, env, tenancy decision, UI theme and logo. No data import. |
| [02-step-1-contacts-and-roles.md](./02-step-1-contacts-and-roles.md) | Contacts (person) entity, contact_emails/contact_phones, CRM roles/permissions. Import: leads + polymorphic contacts → contacts + contact details. |
| [03-step-2-users-and-contact-link.md](./03-step-2-users-and-contact-link.md) | users.contact_id and User↔Contact link. Import: link users to contacts via lead_id→contact_id map. |
| [04-step-3-projects-and-lots.md](./04-step-3-projects-and-lots.md) | Projects and lots tables (organization_id, userstamps). Import from MySQL projects/lots. |
| [05-step-4-reservations-sales-commissions.md](./05-step-4-reservations-sales-commissions.md) | Property reservations, enquiries, searches, sales, commissions — all with contact_id FKs. Import with lead_id→contact_id mapping. |
| [06-step-5-tasks-relationships-marketing.md](./06-step-5-tasks-relationships-marketing.md) | Tasks, relationships, partners, mail lists, brochure jobs, website contacts, questionnaires, finance assessments. Import with contact_id mapping. |
| [07-step-6-ai-native-features.md](./07-step-6-ai-native-features.md) | AI-native features: Laravel AI agents (contact/property assistants), RAG/pgvector, Prism for ad-hoc generation, legacy AI bot config migration. |
| [08-step-7-reporting-and-dashboards.md](./08-step-7-reporting-and-dashboards.md) | Reporting and dashboards: KPIs by role, reservation/task/sales reports, AI-generated dashboard insight. |
| [09-coverage-audit.md](./09-coverage-audit.md) | **100% coverage checklist**: every legacy table → step & import; every import command; every AI enhancement by step. Use to verify nothing is missing. |
| [10-mysql-usage-and-skip-guide.md](./10-mysql-usage-and-skip-guide.md) | **MySQL (Herd replica) row counts**: which tables are empty (skip), low-use (optional), or must-import. Baseline for verification. |
| [11-verification-per-step.md](./11-verification-per-step.md) | **Verifiable results & full data import**: expected row counts and verification commands per step (`fusion:verify-import-*`) so you can confirm data was fully imported. |
| [CHIEF.md](./CHIEF.md) | **Chief / autonomous build**: Entry point so Chief (or any agent) can run the full rebuild from the task list. Only 2 human gates: pre-flight credentials + final sign-off. Self-heals failures autonomously between tasks. |
| [chief-tasks.md](./chief-tasks.md) | **Chief task list**: One task per step in execution order (0→3→1→2→4→5→6→7→14…24, optional 25); step file path, verification, and visual QA check per task. |
| [12-ai-execution-and-human-in-the-loop.md](./12-ai-execution-and-human-in-the-loop.md) | **For AI agents**: How to read the plan, execute step-by-step, and **when to STOP for human review**. Human-in-the-loop at the end of **every** step (or 2-gate autonomous when using CHIEF.md + chief-tasks.md). |
| [00-visual-qa-protocol.md](./00-visual-qa-protocol.md) | **Autonomous visual QA protocol**: Browser MCP (Playwright + browser-tools) checks per task — console errors, brand tokens, DOM element presence, layout verification, accessibility audits. Self-healing table for common failures. |
| [00-websites-puck-builder.md](./00-websites-puck-builder.md) | **Websites, Flyers & Puck Builder spec**: Full parity plan for WordPress provisioning, campaign sites, PHP sites, and flyer PDF generator from legacy v3. Plus new Puck visual page builder (`@measured/puck`) with live CRM data components (ProjectHero, LotGrid, EnquiryForm, etc.) for creating campaign sites and flyers with real property data. Applies to Steps 3, 5, and 17. |
| [13-ai-native-features-by-section.md](./13-ai-native-features-by-section.md) | **AI-native ideas per section**: Expanded suggestions (duplicate detection, similar projects, RAG, email drafts, etc.) so nothing is missed. |
| [14-step-8-phase-1-parity.md](./14-step-8-phase-1-parity.md) | **Phase 1 parity**: Lead attribution, sales pipeline (Kanban/list), follow-up logic, reservation form v2, quick-edit, bulk updates, member listings, multi-channel publish, conversion funnel view. |
| [15-step-9-phase-2-ai-lead-generation.md](./15-step-9-phase-2-ai-lead-generation.md) | **Phase 2**: AI lead gen — multi-channel capture, nurture sequences, cold outreach, landing page AI, lead score/routing, social, coaching/voice. |
| [16-step-10-phase-2-ai-core-and-voice.md](./16-step-10-phase-2-ai-core-and-voice.md) | **Phase 2**: AI core & voice — Bot v2, Vapi, smart summaries, Concierge, auto-content, predictive suggestions, funnel engine. |
| [17-step-11-phase-2-property-builder-push-portal.md](./17-step-11-phase-2-property-builder-push-portal.md) | **Phase 2**: Property, builder portals, push portal — match, inventory API, agent panel, validation, versioning, white-label, compliance. |
| [18-step-12-phase-2-crm-analytics.md](./18-step-12-phase-2-crm-analytics.md) | **Phase 2**: CRM enhancements & analytics — collaboration, custom fields, automation, AI analytics, deal forecasting. |
| [19-step-13-phase-2-marketing-content.md](./19-step-13-phase-2-marketing-content.md) | **Phase 2**: Marketing & content — ad/social templates, brochure v2, retargeting, email campaigns, landing page gen. |
| [20-step-14-phase-2-deal-tracker.md](./20-step-14-phase-2-deal-tracker.md) | **Phase 2**: Deal tracker — document vault, payment tracker, important notes panel. |
| [21-step-15-phase-2-websites-api.md](./21-step-15-phase-2-websites-api.md) | **Phase 2**: Websites & API — Fusion API v2, Zapier/Make, open API docs. |
| [22-step-16-phase-2-xero-integration.md](./22-step-16-phase-2-xero-integration.md) | **Phase 2**: Xero integration — OAuth2, contact/invoice sync, reconciliation, dashboards, payment triggers, queued jobs. |
| [23-step-17-phase-2-signup-onboarding.md](./23-step-17-phase-2-signup-onboarding.md) | **Phase 2**: Auto signup & guided onboarding — plans, payment, provisioning, onboarding checklist. |
| [24-step-18-phase-2-rd-special.md](./24-step-18-phase-2-rd-special.md) | **Phase 2**: R&D & special — Geanelle's list (suburb AI, brochure extraction, email builders); Joey's/Geanelle's. |
| [25-sync-two-way-30min.md](./25-sync-two-way-30min.md) | **Optional / coexistence**: Two-way sync between MySQL (legacy) and PostgreSQL (new app) every 30 minutes for updated data. |

## Design principles

- **Single person entity**: **Contact** replaces “lead as the only person table.” Contacts have type (lead, client, agent, partner, etc.) and stage. Contact details (email, phone) live in contact_emails / contact_phones (or contact_methods).
- **No lead_id everywhere**: Every table that referred to a person now uses **contact_id** or a named FK (e.g. client_contact_id, agent_contact_id). **User** links to one Contact via **users.contact_id**.
- **Two lead types**: **(1) Property leads** — for properties, created by superadmin/admin and subscribers (contact_origin = property). **(2) Software (SaaS) leads for v4** — product demo/trial requests (contact_origin = saas_product); captured via forms, converted to signup in Step 23.
- **Subscriber = org owner in v4:** Using the kit’s organization structure, each subscriber has **their own organization** (one org per subscriber, `organizations.owner_id` = that user) and can **manage their team** under that org. So each v3 subscriber is an org owner in v4; new signups (Step 23) also get a new org with the new user as owner.
- **Starter kit as source of truth**: New app is built from the kit’s migrations, docs (`docs/developer/backend/` and `docs/developer/frontend/`), and conventions: **Inertia + React** for frontend pages, **actions**, **DataTables**, **Filament**, seeders, **Prism**, **Laravel AI SDK**, pgvector.
- **Payment**: **Stripe** for subscription/signup (kit plans & webhooks). **eWAY + Saloon** for reservation/deposit flows. See **00-payment-gateway-decision.md**.
- **Legacy IDs**: **contacts.legacy_lead_id** (nullable, indexed) required for import map and sync; optional **legacy_id** on projects/lots/sales as needed.
- **Airtable sync**: **Deferred** — not in scope for current rebuild; revisit in a later phase if required.
- **Flyer PDFs**: **Spatie Laravel PDF v2** ([docs](https://spatie.be/docs/laravel-pdf/v2/introduction)) — not PDFCrowd/Browsershot as primary.

## Kit automation quick reference

Use these starter-kit commands to generate boilerplate quickly and keep implementation consistent. See **12-ai-execution-and-human-in-the-loop.md** §7–8 for full detail and per-step doc lists.

| Command | Purpose |
|---------|---------|
| `php artisan make:model:full ModelName --migration --factory --seed --category=essential` | Model + migration + factory + seeder + LogsActivity; use `--all` to add controller, policy, form requests. |
| `php artisan seeders:sync` | Create missing seeders for all models (`--dry-run` to preview). |
| `php artisan seeds:from-prose "description" --model=ModelName` | Generate seed spec from natural language (optional). |
| `php artisan make:filament-resource Model --generate --view` | Filament resource with form, infolist, table, pages. |
| `php artisan make:data-table Model` | DataTable class; add `--export --route` for export and route. |
| `php artisan make:data ModelData` | DTO class (see `docs/developer/backend/search-and-data.md`). |

Each step file (02–06) includes a **Suggested implementation order** and **Models to generate** so you can run these generators first, then add plan-specific columns and import commands.

## Execution order

Step file numbers (e.g. 14) map to narrative step numbers (e.g. Step 8 = Phase 1 parity); the plan uses both. Order below lets super admin test **projects/properties** first, then contacts and users.

1. **Step 0** — Create new app from kit; configure PostgreSQL and tenancy.
2. **Step 3** — Add projects/lots (and developers, suburbs, flyers, etc.); run **fusion:import-projects-lots**. Super admin can test property data and UI.
3. **Step 1** — Add contacts + contact details; seed CRM roles; run **fusion:import-contacts** (builds lead_id→contact_id map).
4. **Step 2** — Add users.contact_id; run **fusion:import-users** (or link pass) to set contact_id from map.
5. **Step 4** — Add reservations, enquiries, searches, sales, commissions; run **fusion:import-reservations-sales**.
6. **Step 5** — Add tasks, relationships, partners, marketing tables; run **fusion:import-tasks-relationships-marketing**.
7. **Step 6** — Add AI agents, RAG, prompt commands; optionally **fusion:import-ai-bot-config**.
8. **Step 7** — Add dashboards and reports.
9. **Step 14** — Phase 1 parity (attribution, pipeline, follow-up, reservation v2, quick-edit, bulk, member listings, multi-channel publish, funnel view).
10. **Step 15** — Phase 2: AI lead generation & outreach.
11. **Step 16** — Phase 2: AI core & voice.
12. **Step 17** — Phase 2: Property, builder & push portal.
13. **Step 18** — Phase 2: CRM enhancements & analytics.
14. **Step 19** — Phase 2: Marketing & content tools.
15. **Step 20** — Phase 2: Deal tracker enhancements.
16. **Step 21** — Phase 2: Websites & API.
17. **Step 22** — Phase 2: Xero integration.
18. **Step 23** — Phase 2: Auto signup & onboarding.
19. **Step 24** — Phase 2: R&D & special (Geanelle's list).
20. **Optional:** [25-sync-two-way-30min.md](./25-sync-two-way-30min.md) — Two-way sync (MySQL ↔ PostgreSQL) every 30 minutes for updated data; implement after Step 5 (or 7) when coexistence is required.

Each step is self-contained: **goal**, **starter-kit references**, **deliverables**, **DB design**, **data import** (source → target, mapping, command name), **AI enhancements** (explicit per step), **verification** (row counts and `fusion:verify-import-*` so results are verifiable and data fully imported), **acceptance criteria**. Use [10-mysql-usage-and-skip-guide.md](./10-mysql-usage-and-skip-guide.md) (from your Herd MySQL replica) to decide what to skip; use [11-verification-per-step.md](./11-verification-per-step.md) to confirm each step’s data import. See [09-coverage-audit.md](./09-coverage-audit.md) for the full table→step and AI-by-step checklist.

## Phased go-live (superadmin first)

You can **go live for superadmin first** and **defer subscriber-specific features** to a second phase. Step order and deliverables stay the same; only which roles have access and when subscriber UI is enabled change.

- **Phase A — Superadmin go-live (e.g. “today”)**  
  After Steps 0–7 (and optionally Step 14 for admin-side parity), launch with **superadmin (and optionally admin) only**. Superadmin can manage: **projects**, **lots**, **resources/materials**, **AI chatbots** (Step 6), **contacts** (all property + SaaS leads), **dashboards and reports** (Step 7), and other admin-only Filament/DataTable features. **Subscriber login** (or subscriber role access) is **disabled or deferred**: e.g. do not grant the subscriber role yet, or do not expose subscriber routes/UI. Step 2 can still create one org per v3 subscriber (data ready for Phase B); subscribers simply do not log in until Phase B.

- **Phase B — Subscriber-specific (e.g. “tomorrow”)**  
  When ready, **enable subscriber-specific features**: allow subscriber login and org-scoped experience; contact list (and related data) scoped to **own leads** (e.g. `created_by` or org owner) for subscribers; subscriber-facing UI (reservation form, tasks, mail lists, team management under their org); and Step 23 (signup/onboarding) when you want new signups to receive their own org.

## For AI agents

If you are an AI given **@rebuild-plan** and the **starter kit repo**:

1. **Read first:** [12-ai-execution-and-human-in-the-loop.md](./12-ai-execution-and-human-in-the-loop.md). It defines read order, execution rules, and **human-in-the-loop**: you must **STOP at the end of each step** and hand back to the human with a summary and the step’s checklist. Do **not** proceed to the next step until the human approves. **Exception:** When running in **Chief / autonomous mode** (see below), proceed to the next step after verification and `composer test:quick` pass.
2. **Quick start:** Copy this **rebuild-plan** folder into the kit directory (e.g. `cp -r rebuild-plan /path/to/laravel-starter-kit-inertia-react/rebuild-plan`). Open the **kit repo** as the workspace (rebuild-plan at `rebuild-plan/`). Then say "Implement Step 0"; after human approval, "Implement Step 3" (projects/lots), then "Implement Step 1" (contacts), then "Implement Step 2" (users), then Step 4, and so on. All steps run in the kit directory — no separate "fusion-crm-new" folder.
3. **Then read:** README → 00-database-design → 09-coverage-audit → 10-mysql-usage-and-skip-guide → 11-verification-per-step → the specific step file (e.g. 02-step-1-contacts-and-roles.md) and the referenced starter kit docs.
4. **Implement one step at a time.** After implementing, run verification (if applicable), output the human checklist from the step’s “Human-in-the-loop (end of step)” section, and state: “**STOP — Human review required.**” (Unless in Chief / autonomous mode.)
5. **AI-native features:** Per-step AI is in each step file and in 09-coverage-audit §3; more ideas in [13-ai-native-features-by-section.md](./13-ai-native-features-by-section.md).

### For Chief (autonomous full build)

To run the **full rebuild autonomously** with [Chief](https://chiefloop.com/) (or any agent that accepts a task list):

- **Entry point:** [CHIEF.md](./CHIEF.md) — workspace, paths, self-healing protocol, and 2-gate human interaction model.
- **Task list:** [chief-tasks.md](./chief-tasks.md) — one task per step in execution order (0 → 3 → 1 → 2 → 4 → 5 → 6 → 7 → 14…24, optional 25). Each task names the step file, verification command, `composer test:quick`, and visual QA check to run.
- **Visual QA:** [00-visual-qa-protocol.md](./00-visual-qa-protocol.md) — autonomous browser MCP checks (Playwright + browser-tools) per task. Chief runs these after each task and self-heals up to 3 attempts before logging and continuing.

**Human gates (only 2):**
1. **Pre-flight (start):** Chief confirms credentials (`.env` DB passwords, API keys, legacy MySQL path). Human provides any missing values, then Chief runs autonomously.
2. **Final sign-off (end):** After all tasks complete, Chief presents a full report (tasks completed, self-healed failures, unresolved failures, screenshots). Human reviews and approves or lists follow-ups.

**Setup:** Put the **kit repo** as the workspace with **rebuild-plan** at `./rebuild-plan`. Give Chief the instruction in CHIEF.md. Paths in chief-tasks.md are workspace-relative (e.g. `rebuild-plan/01-step-0-bootstrap.md`). **Prerequisites for import steps (Tasks 2–6):** legacy MySQL must be available and **mysql_legacy** configured in the kit (Step 0 sets this up).

## Human-in-the-loop

Every step (0–7 and 14–24) ends with a **Human-in-the-loop (end of step)** section. The human must:

- Verify the step’s deliverables and verification result (e.g. import PASS, row counts match).
- Complete the checklist in that section (e.g. “Confirm migrations ran”, “Confirm verification PASS”).
- **Approve proceeding to the next step** before any AI or developer continues.

This ensures results are verifiable and data is fully imported before moving on.

## 100% coverage checklist

- **Tables:** Every legacy table is in 09-coverage-audit §1 and 10-mysql-usage-and-skip-guide (assignee: step or skip).
- **Imports:** Every import command and source/target in 09-coverage-audit §2 and in the step files.
- **AI:** Every step’s AI in the step file and 09-coverage-audit §3; extra ideas in 13-ai-native-features-by-section.
- **Verification:** Every step that imports data has expected counts and verify command in 11-verification-per-step and in the step file.
- **Human checkpoints:** Every step has “Human-in-the-loop (end of step)” with a concrete checklist (see 12-ai-execution-and-human-in-the-loop).

## References

- **Starter kit**: `/Users/apple/Code/cogneiss/Products/laravel-starter-kit-inertia-react` — `README.md`, `docs/developer/`, `docs/developer/backend/` (database, actions, data-table, prism, ai-sdk, single-tenant-mode, pgvector, ai-memory), `composer.json`, `database/migrations/`.
- **Current Fusion CRM**: `/Users/apple/Code/clients/piab/fusioncrmv3` — `1-High-Level-Architecture.md`, `features.md`, `4-Core-Code-Files.md`, `app/Models/`, `database/migrations/`.
