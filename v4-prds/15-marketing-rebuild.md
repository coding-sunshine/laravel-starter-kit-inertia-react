# PRD 15: Marketing Rebuild (From Scratch)

> **Phase 2** — Runs after subscriber migration. Requires contacts and email infrastructure.

## Overview

Rebuild the marketing module from scratch (NOT migrating v3 data). Includes email campaigns, nurture sequences, mail lists (new schema), brochure/flyer generation, email settings per org, and email tracking. This replaces the v3 marketing features with a modern event-driven design.

**Prerequisites:** PRDs 00-14 complete (deal tracker, contacts with tags, tasks, notes all working).

## Technical Context

- **Email scheduling:** `thomasjohnkane/snooze` for delayed/scheduled email dispatch
- **Email tracking:** `backstage/laravel-mails` auto-logs ALL sent emails to DB (bounce/delivery tracking)
- **Email templates:** Starter kit's `EmailTemplatesController` + org-scoped `mail_templates` (wraps `martinpetricko/laravel-database-mail`). CRUD at `/settings/email-templates`.
- **Flyers:** Starter kit's `GeneratePdf` action + `GeneratePdfJob` for PDF generation; `@measured/puck` (v0.20.2) for flyer editor (already in starter kit)
- **Tags:** `spatie/laravel-tags` for mail list segmentation
- **Module:** `modules/module-crm/`, namespace `Cogneiss\ModuleCrm`
- **Queue:** Laravel queue for campaign dispatch, bulk sends

### Required Environment Variables
Before starting this PRD, verify these are set in `.env`:
- `MAIL_MAILER`, `MAIL_HOST`, `MAIL_PORT`, `MAIL_USERNAME`, `MAIL_PASSWORD`, `MAIL_ENCRYPTION` — SMTP configuration for email campaigns
- `MAIL_FROM_ADDRESS`, `MAIL_FROM_NAME` — Default sender identity

**If any required key is missing, ASK the user before proceeding. Do not skip, stub, or use placeholder values.**

## User Stories

### US-001: Email Campaign Infrastructure

**Status:** todo
**Priority:** 1
**Description:** Create the email campaign system with scheduling and tracking.

- [ ] Create migration `create_email_campaigns_table`: id, organization_id (FK), name (string), subject (string), body_html (text), body_text (text nullable), mail_list_id (FK mail_lists nullable), status (enum: draft/scheduled/sending/sent/cancelled), scheduled_at (timestamp nullable), sent_at (timestamp nullable), total_recipients (int default 0), created_by (FK users), timestamps
- [ ] Create `Cogneiss\ModuleCrm\Models\EmailCampaign` with `BelongsToOrganization`
- [ ] Create migration `create_campaign_recipients_table`: id, campaign_id (FK email_campaigns CASCADE), contact_id (FK contacts nullable), email (string), status (enum: pending/sent/delivered/opened/clicked/bounced/failed), sent_at, opened_at, clicked_at, timestamps
- [ ] Create `Cogneiss\ModuleCrm\Models\CampaignRecipient`
- [ ] Create `SendCampaignAction`: dispatches `SendCampaignJob` which iterates recipients, sends via Laravel Mail, uses `thomasjohnkane/snooze` for scheduling
- [ ] `backstage/laravel-mails` auto-tracks delivery/bounce for each sent email
- [ ] Campaign dashboard: total sent, delivered, opened, clicked, bounced (aggregated from recipients)
- [ ] Verify: creating a campaign, scheduling it, and triggering send delivers emails to recipients with tracking
- [ ] Email templates stored in starter kit's `mail_templates` table (org-scoped, customizable at `/settings/email-templates`)
- [ ] Sent emails auto-logged via backstage/laravel-mails

### US-002: Mail Lists (New Schema)

**Status:** todo
**Priority:** 1
**Description:** Rebuild mail lists with contact-based membership and external email support.

- [ ] Existing `mail_lists` and `mail_list_contacts` tables from Step 5 import — verify schema matches: mail_lists (id, organization_id, name, description, created_by, timestamps), mail_list_contacts (mail_list_id, contact_id nullable, external_email nullable)
- [ ] Create `MailListDataTable` in `modules/module-crm/src/DataTables/`: columns — name, contact_count (computed), external_count (computed), created_by, created_at
- [ ] Inertia page at `/crm/mail-lists` with DataTable
- [ ] Create/edit slide-over: name, description, multi-select contact search (Typesense), textarea for external emails (one per line)
- [ ] "Export List" action: CSV of all emails (contact emails + external)
- [ ] "Send Brochure" row action: links to brochure mail job creation with pre-selected list
- [ ] Verify: creating a mail list with 5 contacts and 2 external emails shows correct counts; export produces CSV

### US-003: Nurture Sequence Management UI

**Status:** todo
**Priority:** 2
**Description:** Build the admin interface for managing nurture sequences and their steps.

- [ ] Inertia page at `/crm/sequences` with DataTable: name, steps count, status (active/inactive), enrolled contacts count, created_by
- [ ] Create/edit page: sequence name, description, active toggle
- [ ] Steps builder: sortable list of steps, each with: channel (email/sms), subject, template content (rich text editor), delay_days
- [ ] "Enroll Contact" action from contact show page → select sequence → starts durable workflow (from PRD 10 US-004)
- [ ] Show enrolled contacts per sequence with status (active step, completed, dropped)
- [ ] Verify: creating a 3-step email sequence and enrolling a contact shows the contact progressing through steps

### US-004: Brochure/Flyer Generation

**Status:** todo
**Priority:** 2
**Description:** Build flyer editor and PDF generation for property marketing materials.

- [ ] Inertia page at `/crm/flyers` with DataTable: title, project, status (draft/published), created_by, created_at
- [ ] Flyer editor page using `@measured/puck` (v0.20.2, already in starter kit) — FlyrEditor React component
- [ ] Pre-built flyer templates: property showcase, price list, open house invitation
- [ ] Auto-populate from project/lot data: images, price, address, description
- [ ] "Generate with AI" button: uses `laravel/ai` to suggest headline, body copy, CTA for the flyer content
- [ ] PDF export via starter kit's `GeneratePdfJob` (wraps `spatie/laravel-pdf`): `GeneratePdfJob::dispatch('crm::flyer', $data, $filename, $flyer)`
- [ ] Brochure mail job: select flyer + mail list → bulk send flyer PDF as email attachment
- [ ] Track sends via `backstage/laravel-mails`
- [ ] Feature-flagged behind `FlyersFeature` (Pennant)
- [ ] Verify: creating a flyer from template, editing in Puck, exporting as PDF produces valid PDF; sending to mail list delivers

### US-005: Email Settings Per Organization

**Status:** todo
**Priority:** 3
**Description:** Allow each org to configure their email sending settings.

- [ ] Store email settings in `organizations.settings` JSON (schemaless): from_name, from_email, reply_to_email, email_signature_html, unsubscribe_footer (boolean default true)
- [ ] Inertia settings page at `/crm/settings/email` for org admins to configure
- [ ] All CRM emails (campaigns, brochures, sequences) use org email settings as sender
- [ ] Unsubscribe link in footer of all marketing emails (CAN-SPAM compliance)
- [ ] Create `unsubscribes` tracking: contact_id + org_id → skip in future sends
- [ ] Verify: org admin sets from_email; campaign email sends with that from address; unsubscribe link works

### US-006: Email Event Templates

**Status:** todo
**Priority:** 3
**Description:** Register CRM email events with the starter kit's existing email template management system.

> **Starter kit provides:** `EmailTemplatesController`, org-scoped `mail_templates` table, Inertia CRUD at `/settings/email-templates` (with variable toolbar, live preview, reset to default). `MailTemplatesSeeder` for defaults.

CRM-specific work:
- [ ] Register CRM email events in `config/email-templates.php` via `CrmModuleServiceProvider::boot()`: `CampaignSentEvent`, `BrochureSentEvent`, `SequenceStepSentEvent`
- [ ] Define CRM-specific template variables per event: `{{ contact.first_name }}`, `{{ org.name }}`, `{{ campaign.name }}`
- [ ] Seed default CRM templates via `CrmMailTemplatesSeeder`
- [ ] Verify: editing the CampaignSentEvent template at `/settings/email-templates` changes the email content received by contacts

### US-007: Campaign Analytics Dashboard

**Status:** todo
**Priority:** 3
**Description:** Build a campaign performance dashboard with key metrics.

- [ ] Inertia page at `/crm/campaigns/analytics`
- [ ] Metrics: total campaigns sent, average open rate, average click rate, bounce rate, top performing campaign
- [ ] Per-campaign detail: recipient list with individual status (sent/opened/clicked/bounced)
- [ ] Date range filter
- [ ] Data sourced from `campaign_recipients` + `backstage/laravel-mails` tracking
- [ ] Verify: after sending a campaign, analytics page shows send/open/click counts
