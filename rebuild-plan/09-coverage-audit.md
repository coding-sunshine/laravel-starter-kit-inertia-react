# Coverage Audit: 100% Table, Import, and AI Checklist

This document ensures **every** legacy Fusion CRM table, **every** data import, and **every** AI improvement is explicitly assigned to a step. Nothing is left implicit.

**Deferred (not in current scope):** **Airtable** bidirectional sync (legacy feature 11.3.1) is **deferred** — not rebuilt for launch; no step assigns Airtable. Revisit in a later phase if required.

---

## 1. Legacy Tables → Step & Import

| Legacy table (MySQL) | Step | Import command / notes |
|----------------------|------|------------------------|
| **leads** | 1 | `fusion:import-contacts` → **contacts** (contact_origin = property); build lead_id→contact_id map. SaaS leads (contact_origin = saas_product) created in Step 23. |
| **contacts** (polymorphic contactable) | 1 | Same command → **contact_emails** / **contact_phones** (model_type=Lead → contact_id) |
| **users** | 0 (kit) + 2 | Step 2: `fusion:import-users` or link pass sets **users.contact_id** from map |
| **organizations** | 0 + 2 | Kit; Step 2: one org per v3 subscriber (owner_id = user). Step 23: one org per new signup. Optional: import of legacy “teams” into orgs or single default org |
| **projects** | 3 | `fusion:import-projects-lots` |
| **lots** | 3 | Same |
| **developers** | 3 | Same or `fusion:import-lookups` (developer_id on projects) |
| **projecttypes** | 3 | Same or `fusion:import-lookups` |
| **states** | 3 or 1 | Lookup; import if used (e.g. developer_state, addresses) |
| **suburbs** | 3 | Lookup; import if used |
| **sources** | 1 | `fusion:import-contacts` or `fusion:import-lookups`; contact.source_id |
| **companies** | 1 | Import **companies**; contact.company_id (optional) or company_name only |
| **property_reservations** | 4 | `fusion:import-reservations-sales` (all lead_id→contact_id) |
| **property_enquiries** | 4 | Same |
| **property_searches** | 4 | Same |
| **sales** | 4 | Same |
| **commissions** | 4 | Same (commissionable → Sale) |
| **tasks** | 5 | `fusion:import-tasks-relationships-marketing` (assigned_id/attached_id → contact_id) |
| **relationships** | 5 | Same (account_id/relation_id → account_contact_id/relation_contact_id) |
| **partners** | 5 | Same (lead_id → contact_id) |
| **mail_lists** | 5 | Same (lead_id → owner_contact_id; client_ids → contact_ids) |
| **brochure_mail_jobs** | 5 | Same (lead_id → owner_contact_id; client_ids → contact_ids) |
| **mail_job_statuses** | 5 | Same (for brochure jobs) |
| **website_contacts** | 5 | Same (lead_id → contact_id) |
| **questionnaires** | 5 | Same (lead_id → contact_id) |
| **finance_assessments** | 5 | Same (loggedin_agent_id → agent_contact_id) |
| **notes** (arcanedev/laravel-notes) | 5 | Same; noteable_type=Lead → Contact, noteable_id → contact_id via map |
| **comments** | 5 | Same; commentable_type/commentable_id (map if subject IDs changed, e.g. Sale) |
| **tags**, **taggables** | 5 | Create schema in Step 5; legacy has 0 rows so import creates tables but 0 rows; Phase 1 bulk tagging (Step 14) uses schema. If legacy had data: taggable_type=Lead → Contact, taggable_id → contact_id. |
| **addresses** (polymorphic) | 5 | Same; addressable_type=Lead → Contact, addressable_id → contact_id |
| **onlineform_contacts** | 5 | Same; model_type=Lead → Contact, model_id → contact_id |
| **campaign_websites** | 5 | `fusion:import-marketing-websites` or in Step 5; user_id → new user_id |
| **campaign_website_templates** | 5 | Same |
| **campaign_website_project** (pivot) | 5 | Same (project_id map if needed) |
| **flyers** | 3 or 5 | After projects/lots; `fusion:import-flyers` (template_id, project_id, lot_id) |
| **flyer_templates** | 3 or 5 | Same |
| **resources**, **resource_groups**, **resource_categories** | 5 | `fusion:import-resources`; created_by/modified_by → user_id |
| **ad_managements** | 5 | Same; created_by/updated_by → user_id (or contact_id if legacy stored lead) |
| **potential_properties** | 3 | `fusion:import-projects-lots` or `fusion:import-lookups`; developer_id, projecttype_id |
| **project_updates** | 3 | After projects; user_id, project_id |
| **spr_requests** | 3 or 4 | After projects; user_id, project_id |
| **column_management** | 5 or 2 | User preferences; user_id (import with users or Step 5) |
| **widget_settings** | 5 or 2 | User preferences; user_id |
| **survey_questions** | 5 | If linked to questionnaires; import with Step 5 |
| **statuses**, **statusables** | 5 | statusable_type=Lead → Contact, statusable_id → contact_id |
| **ai_bot_categories** | 6 | `fusion:import-ai-bot-config` |
| **ai_bot_prompt_commands** | 6 | Same |
| **ai_bot_boxes** | 6 | Same (ai_bot_category_id) |
| **login_histories** | 2 or skip | user_id; optional import or rely on kit/session |
| **websites** (PHP sites) | 5 | user_id; import if needed |
| **website_pages**, **website_elements** | 5 | With websites |
| **wordpress_websites**, **wordpress_templates** | 5 | user_id; import if needed |
| **email_settings** | 2 or 5 | user_id; import with user preferences |
| **teams**, **team_user** | 0/2 | Kit uses organizations; skip or map to default org membership |
| **permission tables** (Spatie) | 0 | Kit; seed CRM roles in Step 1 |
| **media** | 0 + per model | Kit; migrate media per model (Lead → Contact, Project, Lot, etc.) in respective steps |
| **action_events** (Nova?) | Skip | Optional; not in kit |
| **developer_state** (pivot) | 3 | With developers |
| **company_service** (pivot) | 1 or 5 | With companies/services |
| **default_fonts**, **file_types** | 5 | Lookups if used by flyers/campaigns |
| **password_resets**, **sessions**, **personal_access_tokens**, **failed_jobs**, **oauth_*** | 0 / skip | Kit or auth; sessions/tokens not migrated |
| **au_towns** | 3 | Same as suburbs source (15,299 rows); import as suburbs or skip and use suburbs only |
| **love_reactants**, **love_reactions**, **love_reactant_***, **love_reacters**, **love_reaction_types** | 5 or skip | Optional: new table `contact_favourites` (contact_id, project_id/lot_id); map reacter (lead)→contact, reactant→project/lot. Or skip and rebuild “favourites” later. See **10-mysql-usage-and-skip-guide.md** §1.4 |

---

## 2. Data Import Commands Summary

**Import commands contract (data import standards):** All `fusion:import-*` commands use the config connection (e.g. `mysql_legacy`), support `--dry-run` and `--chunk=N` (or document if not), and may share a base class/trait for connection, dry-run, and chunking. Imports should be **idempotent** where possible (e.g. updateOrCreate keyed by legacy id or business key) so re-running after a fix does not create duplicates. See **12-ai-execution-and-human-in-the-loop.md** §9.

| Command | Step | Source tables | Target |
|---------|------|---------------|--------|
| `fusion:import-contacts` | 1 | leads, contacts (contactable), sources, companies | contacts, contact_emails, contact_phones; lead_id→contact_id map |
| `fusion:import-users` (or link) | 2 | users | users.contact_id from map; optional user prefs (column_management, widget_settings, email_settings) |
| `fusion:import-projects-lots` | 3 | projects, lots, developers, projecttypes, states, suburbs, potential_properties, project_updates, spr_requests, flyers, flyer_templates | New PostgreSQL tables |
| `fusion:import-reservations-sales` | 4 | property_reservations, property_enquiries, property_searches, sales, commissions | New tables with contact_id FKs |
| `fusion:import-tasks-relationships-marketing` | 5 | tasks, relationships, partners, mail_lists, brochure_mail_jobs, mail_job_statuses, website_contacts, questionnaires, finance_assessments, notes, comments, tags, taggables, addresses, onlineform_contacts, campaign_websites, campaign_website_templates, campaign_website_project, resources, resource_groups, resource_categories, ad_managements, statuses, statusables, survey_questions, column_management, widget_settings, websites, website_pages, website_elements, wordpress_websites, wordpress_templates, default_fonts, file_types | New tables; all lead_id/commentable/noteable/taggable/addressable → contact_id or mapped IDs |
| `fusion:import-ai-bot-config` | 6 | ai_bot_categories, ai_bot_prompt_commands, ai_bot_boxes | ai_bot_categories, ai_bot_prompt_commands, ai_bot_boxes (org-scoped) |

Media: Either migrate in each step (e.g. Lead media → Contact in Step 1; Project/Lot media in Step 3) or one `fusion:import-media` after all entities exist, using model_type/model_id mapping (Lead→Contact, etc.).

---

## 3. AI Enhancements by Step (Explicit)

| Step | AI enhancement | Where |
|------|----------------|-------|
| **1** | **Contact type suggestion**: During import, use Laravel AI (or Prism) to suggest contact type from first_name, last_name, company_name (e.g. "lead" vs "partner"). Optional; can default from is_partner. | Import command or post-import job |
| **2** | None required. Optional: AI to suggest “best” contact for a user when multiple leads match. | — |
| **3** | **Project description summary**: Optional Laravel AI to generate short summary or meta description from project description. **Stage suggestion**: Suggest project stage from description. | Filament/Inertia when saving project or background job |
| **4** | **Sale/commission summary**: Prism or Laravel AI to summarize comm_in_notes/comm_out_notes or generate one-line commission breakdown for display. Optional. | Sale show page or Filament |
| **5** | **Task suggestions**: AI to suggest next tasks for a contact (e.g. “Follow up”, “Send proposal”) based on stage and history. **Mail list segmentation**: Optional AI to suggest segments or tags for contacts. | Task index or contact show; Mail list UI |
| **6** | **Contact assistant agent** (Laravel AI) with memory and RAG. **Property/Sales agent** with read-only tools and optional RAG. **Prompt commands** (Prism/Laravel AI). **Ad-hoc drafts**: Email draft, sale summary, next-step text from Prism. **Import**: ai_bot_boxes + categories + prompt_commands. | Agents, chat UI, Filament actions, import command |
| **7** | **Dashboard insight**: Prism or Laravel AI to generate one or two sentence “insight” or recommendation (e.g. “3 high-value leads need follow-up”) from dashboard KPIs. | Dashboard controller, cached or on-load |

---

## 4. Gaps That Were Filled

- **Notes**: Polymorphic notes (noteable → Lead) → noteable_type = Contact, noteable_id = contact_id; import in Step 5.
- **Comments**: Polymorphic; import in Step 5; map commentable_id when type is Sale (new sale id).
- **Tags/Taggables**: Lead → Contact in taggables; import in Step 5.
- **Addresses**: Polymorphic addressable (Lead) → Contact; import in Step 5.
- **Companies**: Table + contact.company_id or company_name; import in Step 1.
- **Sources**: Lookup; contact.source_id; import in Step 1.
- **Campaign websites, templates, pivot**: user_id; import in Step 5.
- **Flyers, flyer_templates**: After projects/lots; import in Step 3 (or 5).
- **Resources, resource_groups, resource_categories**: created_by/modified_by users; import in Step 5.
- **Potential properties, project_updates, spr_requests**: Import in Step 3.
- **Ad managements, column_management, widget_settings**: user_id; import in Step 5 (or 2).
- **Onlineform_contacts**: model_type=Lead → Contact; import in Step 5.
- **Statuses/Statusables**: Lead → Contact; import in Step 5.
- **Ai_bot_boxes**: Import in Step 6 with AI config.
- **Media**: Per-entity migration (Lead→Contact in Step 1; Project/Lot in Step 3; etc.) or single media import command with morph mapping.
- **Verification**: Each step has verifiable results and full-import checks. See **11-verification-per-step.md** for expected row counts and `fusion:verify-import-*` commands. Baseline counts from your Herd MySQL replica: **10-mysql-usage-and-skip-guide.md**.
- **Human-in-the-loop**: Every step has a “Human-in-the-loop (end of step)” section with a checklist; the human must approve before proceeding. See **12-ai-execution-and-human-in-the-loop.md**.
- **AI-native (expanded)**: More ideas per section (duplicate detection, similar projects, RAG, email drafts, etc.) in **13-ai-native-features-by-section.md**.

---

## 5. Verification

- Every legacy **table** appears in § 1 with a **Step** and **Import** reference.
- Every **import command** is listed in § 2 with **source** and **target**.
- Every **AI enhancement** is listed in § 3 by **step** with a concrete **where** (import, UI, agent, dashboard).
- **Nothing is “optional” without a place**: Optional items are explicitly listed (e.g. “Optional: AI contact type” in Step 1) so implementers can choose to add them.

Use this audit when implementing to confirm **100% coverage** of tables, imports, and AI improvements.
