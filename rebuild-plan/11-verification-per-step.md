# Verification Per Step — Verifiable Results & Full Data Import

Each step must end with **verifiable results** so you can confirm data was **fully imported**. Use this document together with **10-mysql-usage-and-skip-guide.md** (baseline row counts from your Herd MySQL replica).

**Execution order:** Steps are run in this sequence: 0 → **3** (projects/lots) → **1** (contacts) → **2** (users) → 4 → 5 → 6 → 7 → … (see README § Execution order).

**Verification command output standard:** Each `fusion:verify-import-*` must produce **machine-parseable** output and use **exit code 0 for PASS**, **non-zero for FAIL**, so verification is scriptable and CI-friendly. Recommended format: one line of `KEY: value, KEY2: value2, RESULT: PASS|FAIL` (or JSON). See **12-ai-execution-and-human-in-the-loop.md** §10.

---

## How to verify

1. **Row-count comparison**: After each import, compare PostgreSQL row counts to the expected counts from the MySQL replica (see §1.3 in `10-mysql-usage-and-skip-guide.md`). Run the verification command or queries below for that step.
2. **Mapping integrity**: Where a map is used (e.g. lead_id→contact_id), ensure every legacy FK was resolved (no orphaned or null FKs where the source had a value).
3. **Optional checksums**: For critical tables, optionally store a checksum (e.g. count + sum of id) of the source before import and compare to target after import.

---

## Step 0 (Bootstrap)

| Check | How |
|-------|-----|
| Env valid | `php artisan env:validate` — exit 0; use `--production` to warn on file/sync in production. |
| Health OK | `php artisan app:health` — exit 0 (database, cache, queue, mail, etc.). |
| Migrations run | `php artisan migrate:status` — all “Ran”. |
| Seed ran | `php artisan seed:environment` exits 0; roles/permissions exist. |
| Tests pass | `composer test` or `composer test:quick` — green. |

No data import; no row verification.

---

## Step 1 (Contacts and roles)

**Expected (from Herd replica):** leads ≈ 9,678; contacts (polymorphic) ≈ 20,074; sources = 17; companies = 969.

| Check | How |
|-------|-----|
| Contacts count | `SELECT COUNT(*) FROM contacts` = **9,678** (or your rule if deduped). |
| Contact details | `SELECT COUNT(*) FROM contact_emails` + `COUNT(*) FROM contact_phones` ≈ **20,074** (or document if you merge email/phone into one table). |
| Sources | `SELECT COUNT(*) FROM sources` = **17**. |
| Companies | `SELECT COUNT(*) FROM companies` = **969**. |
| Map persisted | Lead_id→contact_id map exists (e.g. `contacts.legacy_lead_id` or `import_mappings` table). Every lead id from MySQL has a corresponding contact. |
| No orphan emails/phones | Every contact_emails.contact_id and contact_phones.contact_id exists in contacts. |

**Verification command (example):**
```bash
php artisan fusion:verify-import-contacts
# Outputs: contacts: 9678, contact_emails: X, contact_phones: Y, sources: 17, companies: 969, map_complete: true/false
```

---

## Step 2 (Users and contact link)

**Expected:** users = 1,483; every user that had a lead_id must have contact_id set.

| Check | How |
|-------|-----|
| Users count | `SELECT COUNT(*) FROM users` = **1,483**. |
| Users with contact_id | `SELECT COUNT(*) FROM users WHERE contact_id IS NOT NULL` = number of legacy users that had lead_id (e.g. 1,483 if all had lead_id). |
| No broken FK | `SELECT COUNT(*) FROM users u LEFT JOIN contacts c ON u.contact_id = c.id WHERE u.contact_id IS NOT NULL AND c.id IS NULL` = **0**. |

**Verification command:**
```bash
php artisan fusion:verify-import-users
# Outputs: users: 1483, users_with_contact_id: X, broken_fk: 0
```

---

## Step 3 (Projects and lots)

**Expected:** projects = 15,381; lots = 120,785; developers = 332; projecttypes = 12; states = 8; suburbs = 15,299; project_updates = 153,273; flyers = 10,147; flyer_templates = 3; potential_properties = 1; spr_requests = 4.

| Check | How |
|-------|-----|
| Projects | `SELECT COUNT(*) FROM projects` = **15,381**. |
| Lots | `SELECT COUNT(*) FROM lots` = **120,785**. |
| Lots reference projects | Every lots.project_id exists in projects. |
| Developers | `SELECT COUNT(*) FROM developers` = **332**. |
| Projecttypes | `SELECT COUNT(*) FROM projecttypes` = **12**. |
| States | `SELECT COUNT(*) FROM states` = **8**. |
| Suburbs | `SELECT COUNT(*) FROM suburbs` = **15,299**. |
| Project updates | `SELECT COUNT(*) FROM project_updates` = **153,273**. |
| Flyers | `SELECT COUNT(*) FROM flyers` = **10,147**. |
| Flyer templates | `SELECT COUNT(*) FROM flyer_templates` = **3**. |

**Verification command:**
```bash
php artisan fusion:verify-import-projects-lots
# Outputs each table count and “all match” / “mismatch: …”
```

---

## Step 4 (Reservations, sales, commissions)

**Expected:** property_reservations = 119; property_enquiries = 701; property_searches = 312; sales = 443; commissions = 19,416.

| Check | How |
|-------|-----|
| Property reservations | `SELECT COUNT(*) FROM property_reservations` = **119**. All agent_contact_id, primary_contact_id, secondary_contact_id resolve to contacts. |
| Property enquiries | `SELECT COUNT(*) FROM property_enquiries` = **701**. |
| Property searches | `SELECT COUNT(*) FROM property_searches` = **312**. |
| Sales | `SELECT COUNT(*) FROM sales` = **443**. All client_contact_id, sales_agent_contact_id, etc. resolve to contacts. |
| Commissions | `SELECT COUNT(*) FROM commissions` = **19,416**. commissionable_type/commissionable_id point to existing sales. |

**Verification command:**
```bash
php artisan fusion:verify-import-reservations-sales
# Outputs counts and FK checks
```

---

## Step 5 (Tasks, relationships, marketing, notes, comments, etc.)

**Expected (from Herd):** tasks = 53; relationships = 7,304; partners = 298; mail_lists = 66; brochure_mail_jobs = 180; mail_job_statuses = 180; website_contacts = 1,218; questionnaires = 31; finance_assessments = 1; notes = 22,418; comments = 4,197; addresses = 24,508; statusables = 147,913; statuses = 214; campaign_websites = 199; campaign_website_project = 327; resources = 138; ad_managements = 10; column_management = 4,430; onlineform_contacts = 19; survey_questions = 7; websites = 566; website_pages = 137; website_elements = 705; wordpress_websites = 205.

| Check | How |
|-------|-----|
| Tasks | `SELECT COUNT(*) FROM tasks` = **53**. assigned_contact_id / attached_contact_id resolve. |
| Relationships | `SELECT COUNT(*) FROM relationships` = **7,304**. |
| Partners | `SELECT COUNT(*) FROM partners` = **298**. |
| Mail lists | `SELECT COUNT(*) FROM mail_lists` = **66**. |
| Brochure mail jobs | `SELECT COUNT(*) FROM brochure_mail_jobs` = **180**; mail_job_statuses = **180**. |
| Website contacts | `SELECT COUNT(*) FROM website_contacts` = **1,218**. |
| Notes | `SELECT COUNT(*) FROM notes` = **22,418**. noteable_type/noteable_id (Contact) resolve. |
| Comments | `SELECT COUNT(*) FROM comments` = **4,197**. commentable_ids resolve. |
| Addresses | `SELECT COUNT(*) FROM addresses` = **24,508**. addressable (Contact) resolve. |
| Statusables | `SELECT COUNT(*) FROM statusables` = **147,913**. statusable_type = Contact, statusable_id in contacts. |
| Statuses | `SELECT COUNT(*) FROM statuses` = **214**. |
| Campaign websites | campaign_websites = **199**, campaign_website_project = **327**. |
| Resources | resources = **138**, resource_groups = **9**, resource_categories = **5**. |
| Column management | `SELECT COUNT(*) FROM column_management` = **4,430**. |

**Verification command:**
```bash
php artisan fusion:verify-import-tasks-marketing
# Outputs all table counts and FK checks
```

---

## Step 6 (AI config)

**Expected:** ai_bot_categories = 11; ai_bot_prompt_commands = 481; ai_bot_boxes = 46.

| Check | How |
|-------|-----|
| Categories | `SELECT COUNT(*) FROM ai_bot_categories` = **11**. |
| Prompt commands | `SELECT COUNT(*) FROM ai_bot_prompt_commands` = **481**. |
| Boxes | `SELECT COUNT(*) FROM ai_bot_boxes` = **46**. |

**Verification command:**
```bash
php artisan fusion:verify-import-ai-bot-config
```

---

## Step 7 (Reporting)

No new data import. Verification: dashboard and report pages load; KPIs match aggregated data from imported tables (e.g. total contacts, total sales).

---

## Media (optional separate step or per-step)

**Expected:** media = 107,181. If you run a dedicated media import:

| Check | How |
|-------|-----|
| Media count | `SELECT COUNT(*) FROM media` = **107,181**. model_type/model_id map to Contact, Project, Lot, etc., after those entities exist. |

**Verification command:**
```bash
php artisan fusion:verify-import-media
```

---

## Implementing the verification commands

- Each `fusion:verify-import-*` command should:
  1. Read **expected counts** from a **baseline file** (e.g. `storage/app/import-baseline.json`) generated **once from your MySQL replica** before import (e.g. `php artisan fusion:export-mysql-baseline` that connects to `mysql_legacy` and outputs `{"contacts": 9678, "contact_emails": ..., ...}`). That way verification matches your actual source DB, not hardcoded numbers. Alternatively use the counts in **10-mysql-usage-and-skip-guide.md** as defaults.
  2. Query the **PostgreSQL** tables and compare counts.
  3. Optionally run FK integrity checks (no orphaned contact_id, etc.).
  4. Output **PASS** or **FAIL** and list any mismatches so you can confirm data is fully imported and results are verifiable.

- **Recommended**: Before running any import, run `fusion:export-mysql-baseline` (or manually record MySQL counts) and save to `import-baseline.json`; then after each step run the corresponding `fusion:verify-import-*` so results are verifiable and data fully imported.
