# MySQL Table Usage (Herd Replica) — What to Import vs Skip

This document is based on **actual row counts** from the Fusion CRM v3 MySQL database (Herd replica of live). Use it to decide which features to import fully, import minimally, or skip.

---

## 1. Row Count Summary (from live replica)

### 1.1 Empty tables — **SKIP import** (no data to migrate)

| Table | Rows | Recommendation |
|-------|------|----------------|
| action_events | 0 | Skip (Nova/UI; not in kit) |
| default_fonts | 0 | Skip |
| failed_jobs | 0 | Skip (kit has own) |
| model_has_permissions | 0 | Skip (use kit Spatie) |
| oauth_access_tokens | 0 | Skip |
| oauth_auth_codes | 0 | Skip |
| oauth_clients | 0 | Skip |
| oauth_personal_access_clients | 0 | Skip |
| oauth_refresh_tokens | 0 | Skip |
| personal_access_tokens | 0 | Skip (kit Sanctum) |
| settings | 0 | Skip |
| taggables | 0 | Create schema in Step 5; import runs but 0 rows; Phase 1 bulk tagging (Step 14) uses schema |
| tags | 0 | Same as taggables |
| team_user | 0 | Skip (kit uses organizations) |
| teams | 0 | Skip |
| widget_settings | 0 | Skip |

### 1.2 Very low use (1–50 rows) — **Import but optional / minimal**

| Table | Rows | Recommendation |
|-------|------|----------------|
| ad_managements | 10 | Import (simple; created_by/updated_by → user_id) |
| ai_bot_boxes | 46 | Import with Step 6 AI config |
| ai_bot_categories | 11 | Import |
| campaign_website_templates | 1 | Import (single template) |
| company_service | 44 | Import if companies/services used; else skip |
| email_settings | 40 | Import with user prefs (Step 2 or 5) |
| file_types | 21 | Import as lookup if flyers/campaigns need it |
| finance_assessments | 1 | Import (1 row) or skip |
| flyer_templates | 3 | Import |
| love_reaction_types | 1 | See love_* below |
| onlineform_contacts | 19 | Import (model_type=Lead → Contact) |
| potential_properties | 1 | Import (1 row) or skip |
| projecttypes | 12 | Import (lookup) |
| questionnaires | 31 | Import |
| resource_categories | 5 | Import |
| resource_groups | 9 | Import |
| roles | 22 | Don’t migrate; seed new roles in kit |
| services | 30 | Import if company_service used |
| sources | 17 | Import (lookup for contacts) |
| spr_requests | 4 | Import |
| states | 8 | Import (lookup) |
| survey_questions | 7 | Import with Step 5 |
| wordpress_templates | 4 | Import if wordpress_websites used |

### 1.3 Core / heavy use — **MUST import** (verify row counts)

| Table | Rows | Step | Verify |
|-------|------|------|--------|
| leads | 9,678 | 1 | contacts.count = 9,678 (or + merged/deduped rule) |
| contacts (polymorphic) | 20,074 | 1 | contact_emails + contact_phones total ≈ 20,074 |
| users | 1,483 | 2 | users.count = 1,483; users with contact_id set = 1,483 |
| projects | 15,381 | 3 | projects.count = 15,381 |
| lots | 120,785 | 3 | lots.count = 120,785 |
| project_updates | 153,273 | 3 | project_updates.count = 153,273 |
| statusables | 147,913 | 5 | statusables.count = 147,913 (statusable_type→Contact) |
| media | 107,181 | Per step / media command | media.count = 107,181 after full media import |
| addresses | 24,508 | 5 | addresses.count = 24,508 |
| notes | 22,418 | 5 | notes.count = 22,418 |
| activity_log | 456,985 | Optional | Prefer skip; if migrating, run chunked import after Steps 1–5 and verify count (document in 09). |
| commissions | 19,416 | 4 | commissions.count = 19,416 |
| relationships | 7,304 | 5 | relationships.count = 7,304 |
| login_histories | 41,255 | 2 or skip | Optional; if import, verify count |
| suburbs | 15,299 | 3 | suburbs.count = 15,299 |
| au_towns | 15,299 | 3 | Same as suburbs source; import as suburbs or skip (use suburbs only) |
| developers | 332 | 3 | developers.count = 332 |
| companies | 969 | 1 | companies.count = 969 |
| flyers | 10,147 | 3 | flyers.count = 10,147 |
| comments | 4,197 | 5 | comments.count = 4,197 |
| column_management | 4,430 | 5 | column_management.count = 4,430 |
| sales | 443 | 4 | sales.count = 443 |
| campaign_websites | 199 | 5 | campaign_websites.count = 199 |
| campaign_website_project | 327 | 5 | pivot rows = 327 |
| property_reservations | 119 | 4 | property_reservations.count = 119 |
| property_enquiries | 701 | 4 | property_enquiries.count = 701 |
| property_searches | 312 | 4 | property_searches.count = 312 |
| brochure_mail_jobs | 180 | 5 | brochure_mail_jobs.count = 180 |
| mail_job_statuses | 180 | 5 | mail_job_statuses.count = 180 |
| mail_lists | 66 | 5 | mail_lists.count = 66 |
| website_contacts | 1,218 | 5 | website_contacts.count = 1,218 |
| partners | 298 | 5 | partners.count = 298 |
| resources | 138 | 5 | resources.count = 138 |
| ai_bot_prompt_commands | 481 | 6 | ai_bot_prompt_commands.count = 481 |
| tasks | 53 | 5 | tasks.count = 53 |
| websites | 566 | 5 | websites.count = 566 |
| website_pages | 137 | 5 | website_pages.count = 137 |
| website_elements | 705 | 5 | website_elements.count = 705 |
| wordpress_websites | 205 | 5 | wordpress_websites.count = 205 |
| user_api_ips | 135 | 2 or skip | Optional |
| user_api_tokens | 1,483 | 2 or skip | Optional (tokens; often not migrated) |
| statuses | 214 | 5 | statuses.count = 214 |
| permissions | 113 | 0 | Seed in kit; don’t copy |
| role_has_permissions | 397 | 0 | Seed in kit |

### 1.4 Love reactions (favourites) — **Optional**

| Table | Rows | Recommendation |
|-------|------|----------------|
| love_reactants | 15,962 | Used for “favourite” projects/lots. Option A: New table `contact_favourites` (contact_id, project_id or lot_id, type). Import reacter_id (lead) → contact_id. Option B: Skip and rebuild as simpler “favourites” later. |

If you import: create `contact_favourites` (contact_id, favouritable_type, favouritable_id) and map love_reactions (reacter = lead) to contact_id, reactant (project/lot) to new project_id/lot_id.

---

## 2. Recommended skips (no data or not needed)

- **action_events, default_fonts, failed_jobs, oauth_*, personal_access_tokens, settings, taggables, tags, team_user, teams, widget_settings, model_has_permissions**: Do not import.
- **activity_log**: Optional; 456k rows. Either skip (fresh log in new app) or run a separate one-off migration with count verification.
- **login_histories**: Optional; 41k rows. Skip unless you need historical logins for compliance.
- **au_towns**: Use as source for suburbs only if suburbs table is populated from it; otherwise import suburbs and skip au_towns.

---

## 3. Verification expectations (baseline from this DB)

When implementing imports, use the row counts above as **expected totals** for verification (see **11-verification-per-step.md**). Example: after `fusion:import-contacts`, `contacts` table must have **9,678** rows (or your defined rule if you merge/dedupe); contact_emails + contact_phones combined should align with **20,074** legacy contact detail rows.

---

## 4. Summary

- **Skip entirely**: 16 empty tables (action_events, teams, tags, oauth_*, etc.).
- **Import minimally / optionally**: finance_assessments (1), potential_properties (1), spr_requests (4), survey_questions (7), questionnaires (31), onlineform_contacts (19), widget_settings (0) — include in commands but low priority.
- **Must import with verification**: leads→contacts, users+contact_id, projects, lots, project_updates, statusables, media, addresses, notes, commissions, relationships, sales, property_*, campaign_*, flyers, mail_*, website_*, partners, resources, ai_bot_*, etc., using the row counts in §1.3 as verification targets.
