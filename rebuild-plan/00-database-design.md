# Fusion CRM Rebuild — Database Design

This document defines the **new PostgreSQL schema** for the rebuilt Fusion CRM, aligned with the **Laravel Starter Kit (Inertia + React)** at `/Users/apple/Code/cogneiss/Products/laravel-starter-kit-inertia-react`. It fixes the Contact/Lead/`lead_id` anti-pattern and maps every old MySQL table/column to the new design.

---

## 1. Starter Kit as Source of Truth

### 1.1 Core Tables (from starter kit migrations)

| Table | Purpose |
|-------|---------|
| `users` | id, name, email, password, 2FA, remember_token, timestamps; later migrations add onboarding, timezone, soft deletes |
| `password_reset_tokens` | email, token, created_at |
| `sessions` | id, user_id, ip_address, user_agent, payload, last_activity |
| `organizations` | id, name, slug, settings (json), owner_id→users, timestamps, created_by, updated_by, soft deletes, deleted_by |
| `organization_user` | organization_id, user_id (PK), is_default, joined_at, invited_by, timestamps |
| `organization_invitations` | (see migrations) |
| `organization_roles` / org-scoped roles | (see 2026_02_13_151811) |
| `permission` (Spatie) | roles, permissions, model_has_roles, role_has_permissions, etc.; optional organization_id on roles |
| `activity_log` | Spatie activity log (subject, causer morphs, description, properties) |
| `media` | Spatie Media Library (model morphs, collection_name, file_name, etc.) |
| `contact_submissions` | name, email, subject, message, status; + organization_id, created_by, updated_by (userstamps) |
| `agent_conversations` | id (uuid), user_id, title, timestamps |
| `agent_conversation_messages` | id, conversation_id, user_id, agent, role, content, attachments, tool_calls, tool_results, usage, meta |
| `embedding_demos` | id, content, embedding (vector), timestamps (pgvector demo) |
| `memories` | (laravel-ai-memory) for agent semantic memory with pgvector |

Content tables in the kit that get `organization_id`: `posts`, `help_articles`, `changelog_entries`, `contact_submissions`, `categories`.

### 1.2 Conventions to Follow

- **Userstamps**: `created_by`, `updated_by` → `users` (nullable), with `Mattiverse\Userstamps\Traits\Userstamps`.
- **Activity log**: Use `LogsActivity` and `getActivitylogOptions()`; sensitive attributes in `config('activitylog.sensitive_attributes')`.
- **Multi-tenant**: New CRM tables that are org-scoped get `organization_id` (nullable if single-tenant mode allows global data). When tenancy is disabled (Filament Settings → Tenancy or `config/tenancy.php` `enabled` => false), UI hides org switcher but schema unchanged.
- **Filament**: Admin resources for CRUD; optional Filament Activity Log plugin.
- **DataTables**: One PHP class per model in `App\DataTables\*`; Inertia pages receive `tableData` from `YourDataTable::makeTable()`.
- **Actions**: Single-action classes in `app/Actions/`, `final readonly`, typed `handle()`.
- **Seeders**: `make:model:full`, category-based (Essential/Development/Production), JSON data, idempotent `updateOrCreate`/`firstOrCreate`.
- **AI**: Prism for ad-hoc OpenRouter; Laravel AI SDK for agents, embeddings, RAG; pgvector for embeddings and laravel-ai-memory.
- **Money**: Use **akaunting/laravel-money** for all monetary columns (price, commission amounts). Use `money()` helper or Money cast for display and API so formatting is consistent (projects/lots prices, sales commissions, reservation deposits). See **00-kit-package-alignment.md**.

---

## 2. New Schema Overview

### 2.1 Single Person Entity: **Contact**

- **Problem (current)**: `leads` is the only person table; `contacts` is polymorphic and stores only contact *details* (email, phone) attached to models (Lead, Project, etc.). Dozens of tables reference `lead_id` (or assigned_id/attached_id/client_id/agent_id/etc.) with unclear semantics.
- **Solution**: Introduce a single **Contact** (or Party) entity. A contact represents a person (or optionally an organization) and can have **types/roles** (e.g. lead, client, agent, partner, subscriber, BDM, affiliate). Important tables reference **one** `contact_id` (or a small set of clearly named FKs) instead of many lead_id-style columns.

### 2.1.1 Two lead contexts (property vs SaaS)

The rebuild supports **two distinct lead types** on the same Contact table, distinguished by **contact_origin** (or equivalent):

1. **Property leads** — People interested in **properties** (the CRM’s core use). Created and managed by **superadmin**, **admin**, and **subscribers** within property organizations. These contacts drive reservations, enquiries, sales, tasks, mail lists, etc. All existing legacy `leads` data and domain tables (reservations, sales, commissions, …) are **property**-origin. Use **contact_origin = `property`** (default). Scoped by **organization_id** (each tenant’s property leads).

2. **Software (SaaS) leads for v4** — People interested in the **Fusion CRM product** (demo request, trial, product enquiry). Captured via public forms (e.g. “Request demo”, “Start trial”); managed by internal/superadmin; convert into **signup** (Step 23) when they become a paying subscriber. Use **contact_origin = `saas_product`**. These contacts typically live in a dedicated organization (e.g. “Fusion SaaS”) or with **organization_id** nullable; they are **not** used in property_reservations, sales, or commission flows. When a SaaS lead converts to signup, create the **user** and link **users.contact_id** to that contact (or create a new contact for the new org).

**Implementation**: Add **contact_origin** (string or enum: `property`, `saas_product`), default `property`, to `contacts`. Index for filtering. All legacy imports set contact_origin = `property`. UI and reports filter by contact_origin so property lists and SaaS lead lists stay separate.

**Visibility by role (who sees which leads):**

- **Subscribers** manage **only their own** property leads; those leads are **visible only to them**. Implement by scoping contact list (and related property data: reservations, tasks, etc.) to **created_by = current user** when the user has the subscriber role. So a subscriber sees only property contacts they created (contact_origin = property, created_by = user_id). Subscribers do **not** see SaaS leads (contact_origin = saas_product) or other subscribers’ property leads.
- **Superadmin and admin** can manage **both** (1) **property leads** — all property contacts in the org(s) they have access to (no created_by filter), and (2) **software (SaaS) leads** — all contacts with contact_origin = saas_product. Apply in query scopes, policies, or DataTable/Filament base query so that admin/superadmin bypass the “own only” restriction.

Ensure **contacts.created_by** is set when a subscriber creates a lead and is indexed (e.g. for `where('created_by', auth()->id())`) so subscriber scoping is efficient.

### 2.2 Core Entities and Relations

- **Contact**
  - One table: `contacts`.
  - Columns: id, organization_id (nullable for single-tenant), **contact_origin** (string or enum: `property`, `saas_product`, default `property`), first_name, last_name, job_title, type (enum or string: lead, client, agent, partner, subscriber, bdm, affiliate, etc.), **stage** (string — 14 confirmed values from live v3: `new`, `nurture`, `not_interested`, `hot`, `settlement_handover`, `call_back`, `unconditional`, `property_reserved`, `signed_contract`, `crashed`, `bc_required`, `land_settled`, `construction`, `property_enquiry`; managed by `spatie/laravel-model-states`; full color map in Step 1), source_id (nullable), company_id (nullable, FK → companies) or company_name (nullable), extra_attributes (json), last_followup_at, next_followup_at, **last_contacted_at** (nullable timestamp — updated on every email/call/task-completed event; drives Days-Since-Contact badge: green <7d, amber 7–30d, red >30d), **lead_score** (nullable smallint 0–100 — AI-computed by Step 6 `LeadScoreJob`; drives colored badge on contact list and record header), **legacy_lead_id** (nullable, indexed — stores v3 `leads.id` for import map, Step 25 sync, and debugging), timestamps, soft deletes, created_by, updated_by.
  - **Legacy ID mapping:** Prefer **contacts.legacy_lead_id** (not a separate mapping-only table) so every contact row can be traced to MySQL; add **legacy_id** (or entity-specific `legacy_project_id`, etc.) on projects, lots, sales where needed for two-way sync and imports.  
  - **Contact details** (email, phone): Either same table **contact_emails** and **contact_phones** (this plan uses these; not contact_methods — see Step 1). Do not use a polymorphic “contact_methods” table (type, value, label) so we don’t reuse the old polymorphic “contacts” that attached to Lead/Project.

- **User ↔ Contact**  
  - **users.contact_id** (nullable, FK → contacts). One user can be linked to one contact (the “person” record). No more `users.lead_id`.  
  - When a user is created from a contact (e.g. “convert lead to user”), set `user.contact_id` and optionally set contact type/stage.

- **Organizations**  
  - Use starter kit’s `organizations` (with **owner_id** → users) and `organization_user`.  
  - Contacts (and all org-scoped CRM data) are scoped by `organization_id` so each tenant has its own data.  
  - **Each subscriber = org owner in v4:** Each v3 subscriber is mapped to an **organization** in v4 with that user as **organization owner** (`organizations.owner_id`). So each subscriber has **their own organization** by default and can **manage their team** under that org (invite users via `organization_user`). New signups (Step 23) also get a new organization with the new user as owner. Superadmin/admin are not necessarily org owners; they have access across orgs (or to a dedicated “admin” org). So: one org per subscriber (owner), team = other members in that org; the kit’s organization structure is used as intended.

- **Roles/Permissions**  
  - Use Spatie + starter kit (global roles + optional org roles).  
  - CRM-specific permissions (e.g. `view contacts`, `manage projects`, `view sales`) are created via `permission:sync-routes` or seeders and assigned to roles.

### 2.3 Domain Tables (all reference Contact where a “person” is needed)

- **Projects**: id, organization_id, **legacy_id** (bigint, nullable, indexed — MySQL projects.id for sync/debug), title, stage, estate, … (property fields), developer_id (→ developers or → contacts if developer is a contact), projecttype_id, timestamps, userstamps. No lead_id.
- **Lots**: id, project_id, **legacy_id** (bigint, nullable, indexed — MySQL lots.id for sync/debug), title, land_price, build_price, stage, … (property fields), timestamps, soft deletes, userstamps.
- **Property reservations**: id, organization_id, property_identifier, lot_id (or project_id + lot reference), purchase_price, **primary_contact_id** (purchaser1 → contact), **secondary_contact_id** (purchaser2 → contact, nullable), **agent_contact_id** (agent → contact), **logged_in_user_id** (user who created it; optional), purchaser_type (json), trustee_name, … (other fields), timestamps, soft deletes, userstamps.
- **Property enquiries**: id, organization_id, **client_contact_id**, **agent_contact_id**, **logged_in_user_id**, property refs, … timestamps, userstamps.
- **Property searches**: id, organization_id, **client_contact_id**, **agent_contact_id**, **logged_in_user_id**, … timestamps, userstamps.
- **Sales**: id, organization_id, **legacy_id** (bigint, nullable, indexed — MySQL sales.id for sync/debug), **client_contact_id**, lot_id, project_id, developer_id, **sales_agent_contact_id**, **subscriber_contact_id**, **bdm_contact_id**, **referral_partner_contact_id**, **affiliate_contact_id**, **agent_contact_id** (if distinct), commission fields (comms_in_total, comms_out_total, piab_comm, sales_agent_comm, etc.), payment_terms, expected_commissions (json), finance_due_date, comments, timestamps, soft deletes, userstamps.
- **Commissions**: Keep polymorphic commissionable (e.g. Sale); or explicit sale_id. No lead_id; commission *recipients* are identified by the sale’s contact FKs.
- **Tasks**: id, organization_id, **assigned_contact_id** (who does the task), **attached_contact_id** (who the task is about; nullable), or assigned_to user_id and attached_to contact_id. Prefer one “person” entity: use **assigned_contact_id** and **attached_contact_id** (both → contacts). If the task is assigned to a “user”, the user’s contact_id gives the person; so we can have assigned_to_user_id (nullable) and attached_contact_id.
- **Partners**: id, organization_id, **contact_id** (replaces lead_id), partner-specific fields, timestamps, userstamps.
- **Relationships**: id, **account_contact_id**, **relation_contact_id** (replaces account_id/relation_id → leads), type, timestamps.
- **Mail lists**: id, organization_id, **owner_contact_id** (replaces lead_id), name, client_ids (json of contact_ids), timestamps, userstamps.
- **Brochure mail jobs**: id, organization_id, **owner_contact_id**, client_contact_ids (json or pivot), timestamps, userstamps.
- **Website contacts**: id, organization_id, **contact_id** (replaces lead_id), source, timestamps.
- **Questionnaires**: id, organization_id, **contact_id**, … timestamps.
- **Finance assessments**: id, organization_id, **agent_contact_id** or **logged_in_user_id**, … timestamps.

All “lead_id” and “client_id”/“agent_id”/“purchaser1_id”/“purchaser2_id”/“sales_agent_id”/“bdm_id”/“referral_partner_id”/“affiliate_id”/“subscriber_id” become **contact_id** or a named FK to `contacts` (e.g. `client_contact_id`, `agent_contact_id`).

### 2.4 Contact Details (emails, phones)

- This plan uses **contact_emails** and **contact_phones** (Option A); see Step 1. Migrate old polymorphic `contacts` (model_type, model_id, type, value) into this structure by mapping model_type=Lead to contact_id (after leads → contacts import).
- **contact_emails** / **contact_phones**: contact_id, type (work/home), value, is_primary, order_column, timestamps.

---

## 3. Table-by-Table / Domain Summary

### 3.1 Contacts & Users

| New table/column | Key columns | Relations |
|------------------|-------------|------------|
| **contacts** | id, organization_id, **contact_origin** (property\|saas_product), first_name, last_name, job_title, type, **stage** (14 values — see §2.2), source_id, company_name, extra_attributes, last_followup_at, next_followup_at, **last_contacted_at** (nullable ts), **lead_score** (nullable smallint 0–100), **legacy_lead_id** (nullable, indexed), timestamps, soft_deletes, created_by, updated_by | belongsTo Organization, User (created_by, updated_by) |
| **contact_emails** (or contact_methods) | contact_id, type, value, is_primary | belongsTo Contact |
| **contact_phones** (or in contact_methods) | contact_id, type, value, is_primary | belongsTo Contact |
| **users** (starter kit + new column) | … existing …, **contact_id** (nullable, FK → contacts) | belongsTo Contact |

### 3.2 Projects & Lots

| Table | Key columns | Relations |
|-------|-------------|-----------|
| **projects** | id, organization_id, **legacy_id** (nullable, indexed), title, stage, estate, … (property fields), **lat** (decimal 10,8 nullable), **lng** (decimal 11,8 nullable), **is_featured** (bool), **featured_order** (int nullable), **description_summary** (text nullable — AI), **is_co_living**, **is_high_cap_growth**, **is_rooming**, **is_rent_to_sell**, **is_exclusive** (booleans), developer_id, projecttype_id, timestamps, created_by, updated_by | belongsTo Organization; optional Developer/Contact |
| **lots** | id, project_id, **legacy_id** (nullable, indexed), title, land_price, build_price, stage, … timestamps, soft_deletes, created_by, updated_by | belongsTo Project |
| **user_project_favourites** | user_id (FK), project_id (FK), created_at. UNIQUE(user_id, project_id) | Pivot: User ↔ Project |
| **special_property_requests** | id, user_id, title, description, state, spr_price (default 55.00), payment_status (pending\|paid\|failed), payment_transaction_id, payment_access_code, request_status (pending\|in_progress\|completed), completed_by (FK users), completed_at, notes, created_by, timestamps, soft_deletes | belongsTo User |
| **potential_properties** | id, organization_id, title, suburb, state, developer_name, description, estimated_price_min, estimated_price_max, status (evaluating\|approved\|rejected), imported_from_csv (bool), csv_row_data (json), created_by, timestamps | belongsTo Organization, User |

### 3.3 Reservations, Enquiries, Searches

| Table | Key columns | Relations |
|-------|-------------|-----------|
| **property_reservations** | id, organization_id, agent_contact_id, primary_contact_id, secondary_contact_id, logged_in_user_id, property_id, lot_id, purchase_price, … | Contact, User |
| **property_enquiries** | id, organization_id, client_contact_id, agent_contact_id, logged_in_user_id, … | Contact, User |
| **property_searches** | id, organization_id, client_contact_id, agent_contact_id, logged_in_user_id, … | Contact, User |

### 3.4 Sales & Commissions

| Table | Key columns | Relations |
|-------|-------------|-----------|
| **sales** | id, organization_id, **legacy_id** (nullable, indexed), client_contact_id, lot_id, project_id, developer_id, sales_agent_contact_id, subscriber_contact_id, bdm_contact_id, referral_partner_contact_id, affiliate_contact_id, agent_contact_id, commission columns, … | Contact (×6+), Lot, Project |
| **commissions** | id, sale_id (FK → sales), **commission_type** (enum: `piab`\|`subscriber`\|`affiliate`\|`sales_agent`\|`referral_partner`\|`bdm`\|`sub_agent`), agent_user_id (FK → users, nullable), rate_percentage (decimal 5,2), amount (decimal 12,2), override_amount (bool), notes (text), timestamps, soft_deletes | belongsTo Sale, User (agent) |

### 3.5 Tasks, Notes, Relationships, Partners

| Table | Key columns | Relations |
|-------|-------------|-----------|
| **tasks** | id, organization_id, assigned_contact_id (or assigned_to_user_id), attached_contact_id, title, due_at, … | Contact, User |
| **notes** | id, organization_id (nullable), noteable_type (string), noteable_id (bigint) — morph to Contact/Project/Sale/PropertyReservation; author_id (FK → users); content (text); type (enum: note\|email_draft\|system, default note); is_pinned (bool default false); timestamps, soft_deletes. **Implementation**: **custom `notes` table** (do NOT use arcanedev/laravel-notes package — it is listed in coverage-audit only for reference; create a custom table for full control). Add **MorphMany notes()** to Contact, Project, Sale, PropertyReservation. Import: noteable_type=Lead → App\\Models\\Contact; noteable_id → contact_id via map. | User; morphTo Contact/Project/Sale |
| **comments** | id, commentable_type, commentable_id (morph), user_id, comment, is_approved, timestamps | User; morphTo Sale/Project/… |
| **tags** | id, name, slug, … | — |
| **taggables** | tag_id, taggable_type, taggable_id (e.g. Contact) | Tag; morphTo Contact |
| **addresses** | id, addressable_type, addressable_id (morph: Contact, …), type, line1, line2, city, state, postcode, country | morphTo Contact |
| **partners** | id, organization_id, contact_id (FK → contacts), status (active\|inactive), partner_type (string nullable), commission_rate (decimal 5,2 nullable), territory (string nullable), notes (text nullable), timestamps, soft_deletes, created_by, updated_by | Contact |
| **relationships** | id, organization_id, account_contact_id (FK → contacts), relation_contact_id (FK → contacts), type (string — e.g. agent, partner, referral, colleague), description (text nullable), timestamps | Contact ×2 |
| **smart_lists** | id, user_id (FK → users), organization_id (nullable FK → organizations), name (varchar 100), filter_state (jsonb), sort_order (int default 0), timestamps | User, Organization |
| **statuses** | id, name, … | — |
| **statusables** | status_id, statusable_type, statusable_id (morph: Contact, …) | Status; morphTo Contact |

### 3.6 Marketing & Content

| Table | Key columns | Relations |
|-------|-------------|-----------|
| **mail_lists** | id, organization_id, name, description, created_by. **mail_list_contacts** pivot: mail_list_id, contact_id (nullable), external_email (nullable) | User (created_by); Contact (via pivot) |
| **mail_job_statuses** | id, brochure_mail_job_id, status, … | BrochureMailJob |
| **brochure_mail_jobs** | id, organization_id, owner_contact_id, … | Contact |
| **website_contacts** | id, organization_id, contact_id, … | Contact |
| **campaign_websites** | id, organization_id, user_id, campaign_website_template_id, site_id, title, url, … | User, CampaignWebsiteTemplate |
| **campaign_website_templates** | id, organization_id, name, … | Organization |
| **campaign_website_project** | campaign_website_id, project_id | Pivot |
| **flyers** | id, template_id, project_id, lot_id, poster_img_id, floorplan_img_id, notes | FlyerTemplate, Project, Lot, Media |
| **flyer_templates** | id, organization_id, name, … | Organization |
| **resources** | id, resource_group_id, title, slug, url, created_by, modified_by | ResourceGroup, User |
| **resource_groups** | id, organization_id, name, … | Organization |
| **resource_categories** | id, … | — |
| **advertisements** (was `ad_managements`) | id, title, image_path, link_url (nullable), status (active\|inactive), display_order (int), created_by, timestamps | User (created_by); Spatie Media Library for image |
| **websites** (PHP sites) | id, user_id, … | User |
| **website_pages**, **website_elements** | website_id, … | Website |
| **wordpress_websites**, **wordpress_templates** | user_id, … | User |
| **column_management** | id, user_id, table_name, columns (json) | User |
| **widget_settings** | id, user_id, widget_key, settings (json) | User |
| **survey_questions** | id, questionnaire_id or standalone, … | Optional Questionnaire |

### 3.7 AI (starter kit + CRM)

| Table | Key columns | Relations |
|-------|-------------|-----------|
| **agent_conversations** | (starter kit) user_id, title | User |
| **agent_conversation_messages** | (starter kit) conversation_id, user_id, agent, role, content, … | User |
| **memories** | (laravel-ai-memory) for agent memory with pgvector | — |
| **ai_bot_categories** | id, name, slug, icon, display_order (sortable), organization_id (nullable), timestamps | Organization |
| **ai_bots** | id, category_id (FK), name, slug, description, icon, is_system (bool), is_active (bool), created_by (nullable FK), timestamps | Category, User |
| **ai_bot_prompts** | id, bot_id (FK), label, prompt_template (text), sort_order, timestamps | Bot |
| **ai_bot_runs** | id, bot_id (FK), user_id (FK), input_context (text), prompt_used (text), output (text), model_used, realtime_data_injected (bool), created_at | Bot, User |
| **ai_bot_prompt_commands** (legacy parity) | id, category_id, name, prompt, sort_order | Category |
| **login_events** | id, user_id (FK → users), ip_address, user_agent, device_fingerprint (string 64, hashed), created_at | User — used by Log In History and Same Device Detection reports (Step 7) |

Optional: RAG over contacts/projects/lots using pgvector (e.g. `contact_embeddings` or a generic `embedding_documents` with morphs).

---

## 4. Migration Mapping: Old MySQL → New PostgreSQL

### 4.1 Person / Lead / Contact

| Old (MySQL) | New (PostgreSQL) | Notes |
|-------------|------------------|------|
| **leads** | **contacts** | Map each lead row to one contact. Set contact.type from lead role/flags (is_partner → partner; or use a mapping table lead_id → contact type). contact.stage from lead stage if any. |
| **contacts** (polymorphic: model_type, model_id, type, value) | **contact_emails** / **contact_phones** (or **contact_methods**) | model_type = Lead → use imported contact_id for that lead. type = email → contact_emails; type = phone → contact_phones. |
| **users.lead_id** | **users.contact_id** | Build lead_id → contact_id map from import; set user.contact_id after contacts and users are imported. |

### 4.2 Tables with lead_id / agent_id / client_id / etc.

| Old table | Old column(s) | New table | New column(s) |
|-----------|---------------|-----------|---------------|
| users | lead_id | users | contact_id |
| tasks | assigned_id (→ leads), attached_id (→ leads) | tasks | assigned_contact_id, attached_contact_id (or assigned_to_user_id + attached_contact_id) |
| property_reservations | loggedin_agent_id, agent_id, purchaser1_id, purchaser2_id | property_reservations | logged_in_user_id (optional), agent_contact_id, primary_contact_id, secondary_contact_id |
| property_enquiries | loggedin_agent_id, agent_id, client_id | property_enquiries | logged_in_user_id (optional), agent_contact_id, client_contact_id |
| property_searches | loggedin_agent_id, agent_id, client_id | property_searches | logged_in_user_id (optional), agent_contact_id, client_contact_id |
| sales | client_id, subscriber_id, sales_agent_id, bdm_id, referral_partner_id, affiliate_id, agent_id | sales | client_contact_id, subscriber_contact_id, sales_agent_contact_id, bdm_contact_id, referral_partner_contact_id, affiliate_contact_id, agent_contact_id |
| partners | lead_id | partners | contact_id |
| relationships | account_id, relation_id (→ leads) | relationships | account_contact_id, relation_contact_id |
| mail_lists | lead_id, client_ids (json) | mail_lists | owner_contact_id, client_ids (json of contact_ids) |
| brochure_mail_jobs | lead_id, client_ids | brochure_mail_jobs | owner_contact_id, client_contact_ids (json or pivot) |
| website_contacts | lead_id | website_contacts | contact_id |
| questionnaires | lead_id | questionnaires | contact_id |
| finance_assessments | loggedin_agent_id | finance_assessments | agent_contact_id or logged_in_user_id |
| notes | noteable_type=Lead, noteable_id | notes | noteable_type=Contact, noteable_id=contact_id; author_id→user_id |
| comments | commentable_type, commentable_id | comments | map commentable_id when type=Sale to new sale id; user_id→user_id |
| taggables | taggable_type=Lead, taggable_id | taggables | taggable_type=Contact, taggable_id=contact_id |
| addresses | addressable_type=Lead, addressable_id | addresses | addressable_type=Contact, addressable_id=contact_id |
| onlineform_contacts | model_type=Lead, model_id | onlineform_contacts | model_type=Contact, model_id=contact_id |
| statusables | statusable_type=Lead, statusable_id | statusables | statusable_type=Contact, statusable_id=contact_id |

### 4.3 Other Tables

- **projects**: Add organization_id, created_by, updated_by. developer_id → developers.
- **lots**: Add created_by, updated_by; project_id unchanged.
- **commissions**: commissionable → Sale; no lead_id.
- **developers**, **sources**, **projecttypes**, **states**, **suburbs**, **companies**: Migrate as lookup/org-scoped tables; no lead_id. contact.source_id, contact.company_id (or company_name only).
- **notes**: noteable_type = Lead → Contact, noteable_id → contact_id; author_id → user_id. Import in Step 5.
- **comments**: commentable_type/commentable_id (e.g. Sale); map commentable_id when type is Sale to new sale id. user_id unchanged.
- **tags/taggables**: taggable_type = Lead → Contact, taggable_id → contact_id.
- **addresses**: addressable_type = Lead → Contact, addressable_id → contact_id.
- **onlineform_contacts**: model_type = Lead → Contact, model_id → contact_id.
- **statuses/statusables**: statusable_type = Lead → Contact, statusable_id → contact_id.
- **potential_properties**: developer_id, projecttype_id; import with Step 3.
- **project_updates**: user_id, project_id; import after projects.
- **special_property_requests** (was `spr_requests`): user_id, project_id (optional, may be null for general research requests); import after projects. Map `spr_requests` columns: `spr_price`, `is_payment_completed` → `payment_status`, `is_request_completed` → `request_status`, `transaction_access_code`, `transaction_id` → `payment_transaction_id`.
- **flyers**, **flyer_templates**: Import after projects/lots (Step 3 or 5).
- **campaign_websites**, **campaign_website_templates**, **campaign_website_project**: user_id; import in Step 5.
- **resources**, **resource_groups**, **resource_categories**: created_by/modified_by → user_id; import Step 5.
- **ad_managements**: created_by, updated_by → user_id; import Step 5.
- **media**: Per-entity: Lead → Contact (Step 1); Project/Lot (Step 3); etc., or single import with morph mapping.

---

## 5. Starter Kit Packages Used in the Plan

| Feature | Package / doc |
|---------|----------------|
| Auth, 2FA | Laravel Fortify |
| Organizations | Kit migrations + `single-tenant-mode.md` |
| Permissions | Spatie Laravel Permission, `permissions.md`, route-based, org roles |
| Activity log | Spatie Laravel Activity Log + Filament Activity Log, `activity-log.md` |
| Media | Spatie Media Library, `media-library.md` |
| Userstamps | wildside/userstamps, `userstamps.md` |
| DataTables | machour/laravel-data-table, `data-table.md` |
| Actions / Controllers | `actions/README.md`, `controllers/README.md` |
| Seeders | `database/seeders.md`, `make:model:full` |
| AI text / OpenRouter | Prism, `prism.md` |
| AI agents, embeddings, RAG | Laravel AI SDK, `ai-sdk.md`; pgvector `pgvector.md`; laravel-ai-memory `ai-memory.md` |
| Filament | Filament admin, `filament.md` |

---

This design removes the “lead_id everywhere” anti-pattern, aligns with the starter kit’s schema and conventions, and provides a clear path for incremental data import from MySQL to PostgreSQL as described in the step-by-step plan (Steps 1–N).
