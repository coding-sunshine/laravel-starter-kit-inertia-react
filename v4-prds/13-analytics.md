# PRD 13: CRM Analytics & Enhancements

> **Phase 2** — Runs after subscriber migration. Requires sales and contact data.

## Overview

Add team collaboration (@mentions, file sharing), custom fields with dynamic forms, advanced task automation (if-this-then-that rules), AI analytics layer (natural-language queries), and AI deal forecasting. Builds on Phase 1 + PRDs 09-12.

**Note:** The original rebuild spec (Step 12/18) is light on analytics tooling detail. This PRD focuses on the core capabilities described: team collaboration, custom fields, automation rules, NL analytics, and deal forecasting. `panphp/pan` integration for deeper analytics can be added as a follow-up if the package is available.

**Prerequisites:** PRDs 00-12 complete (property builder, listing management, AI agents working).

## Technical Context

- **AI:** `laravel/ai` for NL query → report and deal probability
- **Activity Log:** `spatie/laravel-activitylog` for @mention tracking
- **Media:** `spatie/laravel-media-library` for file sharing per deal/contact
- **Inertia:** Custom React form components for dynamic field rendering
- **Module:** `modules/module-crm/`, namespace `Cogneiss\ModuleCrm`
- **Queue:** Laravel queue for automation rule execution

### Required Environment Variables
Before starting this PRD, verify these are set in `.env`:
- `DB_CONNECTION=pgsql` — PostgreSQL connection (set in preflight)

No special API keys required for this PRD.

**If any required key is missing, ASK the user before proceeding. Do not skip, stub, or use placeholder values.**

## User Stories

### US-001: Team Collaboration — @Mentions

**Status:** todo
**Priority:** 1
**Description:** Enable @mentions in notes and comments with notifications to mentioned users.

- [ ] Extend notes/comments text input to support @username syntax (frontend: mention autocomplete component)
- [ ] On save, parse @mentions from content; resolve to user IDs
- [ ] Create notification: `UserMentionedNotification` sent to each mentioned user (database + optional email via `laravel-database-mail`)
- [ ] Store mention metadata in activity_log or note metadata (jsonb)
- [ ] Show mention as clickable link in rendered note/comment
- [ ] Verify: typing @john in a note autocompletes to a user; saving sends notification to that user

### US-002: Team Collaboration — File Sharing

**Status:** todo
**Priority:** 2
**Description:** Allow file attachments on contacts and sales using Spatie Media Library.

- [ ] Add media collections to Contact model: `registerMediaCollections()` with 'attachments' collection
- [ ] Add media collections to Sale model: 'documents' collection (contracts, EOIs, etc.)
- [ ] File upload component on contact show page and sale detail (drag-and-drop zone)
- [ ] List uploaded files with: name, size, uploaded_by, uploaded_at, download link
- [ ] Permission: only users with edit permission on entity can upload; all team can view
- [ ] Verify: uploading a PDF to a contact shows in attachments list; download works

### US-003: Custom Fields System

**Status:** todo
**Priority:** 1
**Description:** Allow per-entity custom fields that render dynamically on forms.

- [ ] Create migration `create_custom_fields_table`: id, organization_id (FK), entity_type (string: contact/sale/project/lot), field_name (string), field_label (string), field_type (enum: text/number/date/select/boolean/textarea), options (jsonb nullable — for select type), is_required (boolean default false), sort_order (int), created_at, updated_at. Unique(organization_id, entity_type, field_name)
- [ ] Create migration `create_custom_field_values_table`: id, custom_field_id (FK custom_fields CASCADE), entity_type (string), entity_id (bigint), value (text nullable), created_at, updated_at. Unique(custom_field_id, entity_type, entity_id)
- [ ] Create `Cogneiss\ModuleCrm\Models\CustomField` and `CustomFieldValue` with `BelongsToOrganization`
- [ ] Add `HasCustomFields` trait to Contact, Sale, Project, Lot models: provides `customFieldValues()` morphMany and accessor helpers
- [ ] Inertia settings page at `/crm/settings/custom-fields` for managing custom fields per entity type (CRUD)
- [ ] Dynamic form rendering: on create/edit pages, append custom fields below standard fields
- [ ] Custom field values searchable in DataTable (via JSON column or join)
- [ ] Verify: admin creates "Preferred Contact Method" custom field for contacts; field appears on contact edit form; value saves and displays

### US-004: Advanced Task Automation Rules

**Status:** todo
**Priority:** 2
**Description:** If-this-then-that automation engine for CRM events.

- [ ] Create migration `create_automation_rules_table`: id, organization_id (FK), name (string), event (string: contact.stage_changed / sale.stage_changed / task.completed / contact.created), conditions (jsonb — array of field/operator/value), actions (jsonb — array of action_type/params), is_active (boolean default true), created_by (FK users), timestamps
- [ ] Create `Cogneiss\ModuleCrm\Models\AutomationRule` with `BelongsToOrganization`
- [ ] Create `EvaluateAutomationRulesAction`: on event fire, find matching active rules for org, evaluate conditions, execute actions
- [ ] Supported actions: create_task, send_email (via database-mail event), update_field, add_tag, notify_user
- [ ] Supported events: contact.created, contact.stage_changed, sale.stage_changed, task.completed, reservation.created
- [ ] Execute via queue job to avoid blocking
- [ ] Inertia page at `/crm/settings/automation-rules` for rule CRUD with condition/action builder
- [ ] Verify: rule "When contact stage changes to qualified, create follow-up task" triggers correctly

### US-005: AI Analytics Layer — Natural Language Queries

**Status:** todo
**Priority:** 2
**Description:** Allow users to ask questions in plain English and get data-driven answers.

- [ ] Create Inertia page at `/crm/analytics`
- [ ] Input: natural language text box (e.g., "What suburb had best ROI Q1?", "How many leads converted last month?")
- [ ] Create `AnalyticsQueryAgent` using `laravel/ai`: agent has tools to query contacts (by stage, date, suburb), sales (by date, agent, project), commissions, lots
- [ ] Agent generates SQL-safe queries via Eloquent (never raw SQL), executes, formats result
- [ ] Display result as text summary + optional chart (bar/line) or table
- [ ] Org-scoped: queries only access current org's data
- [ ] Rate-limited: max 20 queries per hour per user (AI credit deduction)
- [ ] Verify: asking "How many contacts were created this month?" returns correct count

### US-006: AI Deal Forecasting

**Status:** todo
**Priority:** 3
**Description:** Predict likelihood of deal/sale close using AI analysis.

- [ ] Create `ForecastDealAction`: accepts sale_id, analyzes: days in stage, contact engagement level, similar past deals, property demand
- [ ] Uses `laravel/ai` with structured output: { probability: 0-100, confidence: low/medium/high, factors: [...] }
- [ ] Show forecast badge on sale detail page and sale list (e.g., "72% likely to close")
- [ ] Refresh forecast on stage change or weekly batch
- [ ] Verify: sale in "Contract" stage shows forecast percentage; forecast updates when stage changes
