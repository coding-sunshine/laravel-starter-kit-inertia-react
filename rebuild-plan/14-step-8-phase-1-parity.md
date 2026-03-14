# Step 14 (Step 8): Phase 1 Parity — Must-Haves After Core Rebuild

## Goal

Deliver **Phase 1 parity** features required for the rebuilt CRM to match day-one usability and core workflows. These are built **after** Steps 0–7 and the AI-native features in **13-ai-native-features-by-section.md**. No new data import from MySQL; all features use the existing PostgreSQL schema and imported data.

## Starter Kit References

For Phase 1/2 steps (14–24), use the same kit docs as Steps 1–7: DataTable, Filament, Actions, Inertia; see **12-ai-execution-and-human-in-the-loop.md** §8 (per-step doc list).

- **DataTable**: `docs/developer/backend/data-table.md` — filters, exports, saved views
- **Filament**: `docs/developer/backend/filament.md` — resources, widgets, actions
- **Inertia**: Dashboard and list/detail page patterns; shared layout
- **Actions**: `docs/developer/backend/actions/README.md` — single-action classes for bulk updates, status changes
- **Permissions**: Kit Spatie roles/permissions (already present); scope CRM permissions for new UI

## Deliverables

1. **Lead source attribution**
   - Extend contact/source tracking so each lead can be attributed to **campaign**, **ad**, or **agent** (contact_id or user_id). Add columns or a small **lead_sources** / **contact_attributions** table (contact_id, campaign_id or campaign_name, ad_id or ad_name, attributed_agent_contact_id, attributed_at, source). Backfill from existing source_id where possible. UI: show attribution on contact profile and in contact/lead reports; filter by campaign/agent in DataTables.

2. **Sales pipeline (Kanban & list views)**
   - **Kanban view**: Sales (or deals) grouped by stage (e.g. enquiry, reservation, contract, settlement). Drag-and-drop or dropdown to change stage. Use existing **sales** table and status/stage column; add or use a **sale_stages** config or lookup. Inertia page with Kanban board component (e.g. sortable columns, cards per sale).
   - **List view**: DataTable for sales with filters (stage, agent, client, date range), sortable columns, quick-edit stage. Export optional.
   - Both views use contact_id-based relations; role-aware (show only permitted sales).

3. **Follow-up task logic**

   **`contact.last_contacted_at` updates when ANY of these direct 1:1 interaction events occur:**
   - Email sent directly to this contact (via "Send Email" action — NOT bulk/automated marketing emails)
   - Call logged on this contact (via "Log Call" action — stores call note)
   - Task completed where `task.type` IN (`call`, `email`, `meeting`, `follow_up`, `visit`)
   - Note added with `note.interaction_type = 'contact'` (direct interaction note — NOT internal notes)
   - Contact stage changed manually by an agent

   **`contact.next_followup_at` rules:**
   - NEVER auto-overwritten by the system. Only set/updated when a user explicitly sets a follow-up date.
   - When a user sets a follow-up: default suggestion pre-filled as `today + follow_up_interval_days` (configurable per org via `ContactSettings`, default: **7 days** for leads, **30 days** for clients).
   - If `next_followup_at < now()` → contact shows as "overdue follow-up" (orange dot) on dashboard and contact list.
   - If `last_contacted_at < now() - stale_threshold_days` → contact shows as "stale" (red dot). Default threshold: **30 days**, configurable per org.

   **Stale contacts:** `last_contacted_at IS NULL AND created_at < now() - stale_threshold_days` OR `last_contacted_at < now() - stale_threshold_days`. These populate the "Stale Contacts" KPI card on the dashboard.

   **Implementation:**
   - `UpdateLastContactedAtAction::handle(Contact $contact, string $reason): void` — called by email, call, task, and note handlers. Logs the trigger reason to `activity_log`.
   - `Task` observer: on `saved()` where `status` changes to `completed`, check `task.type`; if interaction type, call `UpdateLastContactedAtAction`.
   - `ContactSettings` (per-org config, stored in `organizations.settings` JSON or `settings` table): `follow_up_interval_days` (default 7 for leads, 30 for clients), `stale_threshold_days` (default 30).
   - Automated/bulk emails (mail jobs, campaigns) do NOT trigger `last_contacted_at` update.

4. **Reservation form v2**
   - **Auto-filled**: Pre-fill primary/secondary contact from selected contact(s); pre-fill agent from current user's contact_id; pre-fill property/lot from selection.
   - **Validated**: Server-side validation (required fields, purchase_price, dates); optional client-side validation; clear error messages.
   - **Stakeholder-mapped**: Form submits to **property_reservations** with primary_contact_id, secondary_contact_id, agent_contact_id, logged_in_user_id correctly set. Inertia form or Filament resource form; use existing migration schema.

5. **Status quick-edit**
   - **Dashboard and pipelines**: On contact list, sale list, or task list, provide a **dropdown or inline control** to change status/stage without opening full edit. Single-action class (e.g. `UpdateContactStageAction`, `UpdateSaleStageAction`) called via AJAX/Inertia; optimistic UI or reload. Permissions: only users with edit permission can quick-edit.

6. **Bulk update tools**
   - **Tagging**: Select multiple contacts (or tasks, sales) and apply/add a tag (or remove). Use existing **tags** and **taggables**; bulk action in DataTable or checkbox + toolbar.
   - **Status change**: Select multiple contacts (or sales) and set status/stage in one action.
   - **Super Group** (if applicable): Bulk add contacts to a group or mail list. Use existing mail_lists.client_ids or equivalent.
   - Implementation: DataTable bulk actions or Inertia page with multi-select; backend action validates permissions and applies updates in a transaction.

7. **Member-uploaded listings (projects/lots) + validation**
   - **Member upload**: Allow users (with permission) to create/edit **projects** and **lots** via a dedicated form or Filament resource; upload media (photos, floor plans) via Spatie Media. "Member" = org user or agent; data scoped by organization_id.
   - **Validation**: Required fields (e.g. title, project stage, lot price); optional validation rules (e.g. price > 0, dates logical). Display validation errors; optionally prevent publish until valid. Consider **listing_status** (draft vs published) if needed.

8. **Multi-channel publishing (PHP + WordPress)**
   - **Push to PIAB PHP sites**: Service or action that syncs selected projects/lots (or all) to existing PHP fast sites (use **websites** table or config for site endpoints). Document API or feed format expected by PHP sites.
   - **Push to WordPress**: Sync listings to WordPress sites (use **wordpress_websites** / **wordpress_templates**). May use WP REST API or plugin; document integration.
   - **Scope**: At least "push to PHP" and "push to WordPress" as two channels; no REA/Domain/MLS in Phase 1 unless already specified. UI: "Publish" or "Push" action per project/lot or bulk; show last pushed at or push history in a simple table/log.

9. **Conversion funnel view**
   - **Lead → Deal → Commission**: Report or dashboard widget that visualises the funnel (e.g. counts by stage: leads by stage, reservations count, sales count, commissions count). Use existing contacts (by stage), property_reservations, sales, commissions. Inertia page with a simple funnel chart (e.g. horizontal bar or stepped) or list of stages with counts. Optional: filter by date range, organization, agent.

## DB Design (this step)

- **Optional new**: **contact_attributions** or **lead_sources** (contact_id, campaign_id/campaign_name, ad_id/ad_name, attributed_agent_contact_id, attributed_at) if not covered by extending contacts or a simple JSON column. Else extend **contacts** with attribution columns (campaign_id, ad_id, attributed_agent_contact_id).
- **Optional**: **listing_validation_rules** or config for project/lot validation (can be config only).
- **Optional**: **push_history** (id, pushable_type, pushable_id, channel [php|wordpress], pushed_at, user_id, response) for multi-channel publishing audit. Else use activity_log.

## Data Import

- **None.** This step builds UI and behaviour on top of existing imported data. Backfill attribution from source_id where possible (one-off job or in migration).

## AI Enhancements

- None required for Phase 1 parity. Optional: use Prism to suggest "next follow-up date" when user logs a follow-up.

## Verification (verifiable results)

- No new row-count verification. Verify: (1) Lead attribution visible on at least one contact and filterable in report. (2) Sales Kanban and list view load and stage change works. (3) Follow-up logic: completing a task does not overwrite next_followup_at unless intended. (4) Reservation form v2 creates a reservation with correct contact_id and agent_contact_id. (5) Status quick-edit works on contacts and sales. (6) Bulk tag and bulk status change work. (7) Member can create a project/lot and upload media; validation blocks invalid submit. (8) Push to PHP and push to WordPress execute without error (manual test or smoke test). (9) Conversion funnel view shows counts for at least two stages.

## Human-in-the-loop (end of step)

**STOP after this step. Do not proceed to Step 15 until the human has completed the checklist below.**

Human must:
- [ ] Confirm lead source attribution is visible and filterable.
- [ ] Confirm sales pipeline (Kanban + list) works and stage updates persist.
- [ ] Confirm follow-up task logic does not overwrite follow-up dates unintentionally.
- [ ] Confirm reservation form v2 submits with correct stakeholder mapping.
- [ ] Confirm status quick-edit and bulk update tools work.
- [ ] Confirm member-uploaded listing flow and validation work.
- [ ] Confirm multi-channel publish (PHP + WordPress) runs successfully at least once.
- [ ] Confirm conversion funnel view displays.
- [ ] Approve proceeding to Step 15 (Phase 2: AI lead generation).

## Acceptance Criteria

- [ ] Lead source attribution (campaign/ad/agent) available and reportable.
- [ ] Sales pipeline has Kanban and list views with stage updates.
- [ ] Follow-up task logic documented and implemented (no silent overwrite).
- [ ] Reservation form v2 is auto-filled, validated, and stakeholder-mapped.
- [ ] Status quick-edit on dashboard/pipelines; bulk update (tags, status) working.
- [ ] Member-uploaded listings with validation; multi-channel publishing (PHP + WordPress) and conversion funnel view delivered.
