# PRD 20: Deferred Features (Phase 3)

> **Phase 3** — These features are deferred until Phases 1 and 2 are complete.

## Overview

Three features explicitly deferred from Phase 1 and 2: Lead Routing (auto-assign property leads), Automated AI Follow-ups (AI-driven sequences), and Co-selling (cross-org property sharing with split commissions). These are documented here for future implementation when the platform has sufficient data and subscriber base to justify the complexity.

**Prerequisites:** PRDs 00-19 complete (all Phase 1 and Phase 2 features delivered and stable).

## Technical Context

- **AI:** `laravel/ai` for scoring algorithms and follow-up generation
- **Workflows:** `laravel-workflow/laravel-workflow` for durable follow-up sequences
- **Module:** `modules/module-crm/`, namespace `Cogneiss\ModuleCrm`
- **Multi-tenancy:** All models use `organization_id` via `BelongsToOrganization` trait
- **Existing infrastructure:** Lead scoring (PRD 10), nurture sequences (PRD 10/15), automation rules (PRD 13), commission tracking (Phase 1 Step 4)

### Required Environment Variables
Before starting this PRD, verify these are set in `.env`:
- `OPENAI_API_KEY` — Required for AI-driven follow-up content generation

**If any required key is missing, ASK the user before proceeding. Do not skip, stub, or use placeholder values.**

## User Stories

### US-001: Lead Routing — Auto-Assign Property Leads

**Status:** todo
**Priority:** 1
**Description:** Auto-assign incoming property leads to the best-fit subscriber based on geography, capacity, and performance metrics.

- [ ] Create migration `create_lead_routing_rules_table`: id, organization_id (FK — PIAB org for platform-level rules), name (string), criteria_type (enum: geo/capacity/performance/combined), criteria_config (jsonb — e.g., { suburbs: [...], max_active_leads: 50, min_conversion_rate: 0.05 }), priority (int — lower = higher priority), is_active (boolean default true), timestamps
- [ ] Create `Cogneiss\ModuleCrm\Models\LeadRoutingRule` with `BelongsToOrganization`
- [ ] Create `RoutePropertyLeadAction`:
  - Input: new Contact with `contact_origin = 'property'`
  - Evaluate rules in priority order:
    1. **Geo match**: contact suburb/postcode matches subscriber's configured territory (from partners table or org settings)
    2. **Capacity check**: subscriber's active lead count < max_active_leads threshold
    3. **Performance score**: subscriber's trailing conversion rate (sales/leads ratio over 90 days)
  - Output: ranked list of subscriber orgs with scores; assign to top match
  - Set `contacts.organization_id` to winning org; set `contacts.assigned_agent_contact_id` if org has default agent
- [ ] Scoring algorithm: `geo_match * 0.4 + capacity_available * 0.3 + performance_score * 0.3`
- [ ] Fallback: if no match, assign to PIAB org (superadmin manually routes)
- [ ] Create `LeadAssignedNotification` sent to receiving org's admin/agents
- [ ] Superadmin UI: manage routing rules (CRUD), view routing log, override assignments
- [ ] Dashboard widget for superadmin: "Leads Routed Today" count + distribution chart by org
- [ ] Verify: new property lead with suburb matching a subscriber's territory auto-assigns to that org; notification sent

### US-002: Automated AI Follow-ups

**Status:** todo
**Priority:** 2
**Description:** AI-driven email/SMS follow-up sequences for property leads using laravel/ai and durable workflows.

- [ ] Create migration `create_ai_followup_sequences_table`: id, organization_id (FK), name (string), trigger_event (enum: contact.created/contact.stage_changed/reservation.created/contact.stale), target_filter (jsonb — e.g., { stage: "enquiry", days_since_last_contact: 7 }), is_active (boolean default true), created_by (FK users), timestamps
- [ ] Create migration `create_ai_followup_steps_table`: id, sequence_id (FK ai_followup_sequences CASCADE), step_order (int), channel (enum: email/sms), delay_days (int), ai_prompt_template (text — instructions for laravel/ai to generate content), fallback_template (text — static template if AI unavailable), subject_template (string nullable — for email), timestamps
- [ ] Create `AiFollowupWorkflow` extending `Workflow` (laravel-workflow/laravel-workflow):
  - Durable: survives restarts, crash-safe
  - Each step: wait `delay_days` → generate personalised content via `laravel/ai` using contact context (name, property interest, engagement history) → send via appropriate channel
  - If AI generation fails: use `fallback_template` with variable substitution
  - Track step completion in workflow state
  - Respect unsubscribe status (skip if contact has unsubscribed)
- [ ] Create `EnrollInAiFollowupAction`: starts workflow for contact + sequence combo
- [ ] Auto-enrollment: automation rules (PRD 13) can trigger enrollment (e.g., "When contact created with stage=enquiry, enroll in Welcome Sequence")
- [ ] Admin UI: CRUD for sequences and steps; preview AI-generated content for a sample contact
- [ ] Reporting: sequence performance — enrolled count, completed count, drop-off per step, conversion count (contacts who progressed to next stage)
- [ ] AI credit deduction per generated message
- [ ] Verify: enrolling a contact in a 3-step AI follow-up sequence generates personalised email on day 1; day 3 step fires on schedule; content varies per contact

### US-003: Co-Selling — Cross-Org Property Sharing

**Status:** todo
**Priority:** 3
**Description:** Enable subscribers to opt-in to share their properties with other subscribers, with referral tracking and split commission calculation.

- [ ] Create migration `create_co_selling_agreements_table`: id, owner_org_id (FK organizations — property owner), seller_org_id (FK organizations — selling subscriber), project_id (FK projects nullable — null = all projects), status (enum: pending/active/suspended/terminated), commission_split_owner (decimal 5,2 — owner's percentage, e.g., 60.00), commission_split_seller (decimal 5,2 — seller's percentage, e.g., 40.00), terms (text nullable), agreed_at (timestamp nullable), created_by (FK users), timestamps. Check constraint: `commission_split_owner + commission_split_seller = 100`
- [ ] Create `Cogneiss\ModuleCrm\Models\CoSellingAgreement` with relationships to both organizations
- [ ] Create migration `create_co_selling_referrals_table`: id, agreement_id (FK co_selling_agreements CASCADE), contact_id (FK contacts — the buyer lead), referred_by_org_id (FK organizations), sale_id (FK sales nullable — linked when sale completes), referral_status (enum: referred/converted/expired), commission_owner_amount (decimal 12,2 nullable), commission_seller_amount (decimal 12,2 nullable), timestamps
- [ ] Create `Cogneiss\ModuleCrm\Models\CoSellingReferral`
- [ ] **Opt-in flow**: subscriber org admin → Settings → Co-Selling → toggle "Share my properties" → select which projects to share (or all)
- [ ] **Visibility**: when co-selling active, seller org sees shared projects in their project list (read-only, marked with "Shared" badge)
- [ ] **Referral tracking**: when seller org's agent creates a sale on a shared project, auto-create `CoSellingReferral` linking buyer contact, sale, and both orgs
- [ ] **Split commission**: on sale completion, calculate commission split per agreement percentages; create two commission records (one per org) using `akaunting/laravel-money` for display
- [ ] **Agreement management**: Filament system panel for superadmin to manage agreements; org admins request/accept via Inertia settings page
- [ ] **Referral dashboard**: per-org view of referrals sent/received, commission earned from co-selling
- [ ] **Permission model**: seller org gets read access to shared projects + lots; cannot edit. Buyer contacts created by seller remain in seller's org but linked to shared project
- [ ] Verify: org A shares Project X; org B sees it in their list with "Shared" badge; org B creates sale on shared lot → referral created → commission split calculated per agreement
