# PRD 10: AI Lead Generation & Outreach

## Overview

> **Phase 2** — Requires subscriber contacts and AI infrastructure from Steps 1+6.

Add AI-driven lead generation and outreach features: multi-channel lead capture, auto-nurture sequences, GPT cold outreach builder, landing page AI copy, campaign optimization, smart lead scoring and routing, social content generation, and lead brief generation. Builds on Phase 1 + PRD 09.

**Prerequisites:** PRDs 00-09 complete (search working, contacts searchable, bulk ops available).

## Technical Context

- **AI:** `laravel/ai` (agents, tools, embeddings) + `prism-php/prism` via ai() helper + OpenRouter
- **Workflows:** `laravel-workflow/laravel-workflow` for durable multi-day nurture sequences
- **Module:** `modules/module-crm/`, namespace `Cogneiss\ModuleCrm`
- **Queue:** Laravel queue for scheduled nurture steps, scoring jobs
- **Models:** All have `organization_id` via `BelongsToOrganization` trait
- **Existing:** Contact model with `lead_score`, ContactAttribution from PRD 09

### Required Environment Variables
Before starting this PRD, verify these are set in `.env`:
- `OPENAI_API_KEY` — Required for AI lead scoring, nurture content generation, and outreach builder

**If any required key is missing, ASK the user before proceeding. Do not skip, stub, or use placeholder values.**

## User Stories

### US-001: Multi-Channel Lead Capture Engine

**Status:** todo
**Priority:** 1
**Description:** Accept leads via forms, phone, SMS, chat, and voice; normalize to Contact with attribution.

- [ ] Create `CaptureLeadAction` in `modules/module-crm/src/Actions/`: accepts channel (form/phone/sms/chat/voice), payload, creates Contact + contact_emails/contact_phones
- [ ] Store channel and source (campaign, ad, agent) in `contact_attributions` table (from PRD 09)
- [ ] Normalize all channels to same Contact creation flow with `contact_origin = 'property'`
- [ ] Webhook endpoints for SMS/chat/voice providers at `/api/crm/leads/capture` with channel discrimination
- [ ] Duplicate detection: check email/phone before creating new contact; merge or link if match found
- [ ] Verify: lead captured via form and via API webhook both create Contact with attribution

### US-002: Lead Scores Table & AI Scoring

**Status:** todo
**Priority:** 1
**Description:** Create lead scoring infrastructure and AI-driven score calculation.

- [ ] Create migration `create_lead_scores_table`: id, contact_id (FK contacts CASCADE), organization_id (FK), score (integer 0-100), factors_json (jsonb), model_used (string nullable), updated_at, created_at
- [ ] Create `Cogneiss\ModuleCrm\Models\LeadScore` with `BelongsToOrganization`
- [ ] Add `leadScore()` hasOne relationship on Contact
- [ ] Create `CalculateLeadScoreAction`: uses `laravel/ai` to analyze contact data (stage, engagement, property interest, recency) and output score + factors JSON
- [ ] Dispatch scoring job on contact creation and on engagement events
- [ ] Expose `lead_score` on ContactDataTable (sortable, filterable range)
- [ ] Show score badge on contact show page
- [ ] Verify: new contact gets a lead score; score visible on contact list and detail

### US-003: Engagement Events Tracking

**Status:** todo
**Priority:** 2
**Description:** Track engagement events (opens, clicks, conversions) for campaign optimization.

- [ ] Create migration `create_engagement_events_table`: id, contact_id (FK contacts CASCADE), organization_id (FK), event_type (string: open/click/reply/conversion/bounce), source (string: email/sms/web), payload (jsonb nullable), created_at
- [ ] Create `Cogneiss\ModuleCrm\Models\EngagementEvent` with `BelongsToOrganization`
- [ ] Create `RecordEngagementAction` for tracking events from various sources
- [ ] Feed engagement data into lead score recalculation
- [ ] Show engagement timeline on contact show page
- [ ] Verify: recording an engagement event updates contact's engagement timeline

### US-004: Auto-Nurture Sequences with Durable Workflows

**Status:** todo
**Priority:** 2
**Description:** Build GPT-generated email/SMS drip campaigns using durable workflows for crash-safe scheduling.

- [ ] Create migration `create_nurture_sequences_table`: id, organization_id (FK), name, description (text nullable), is_active (boolean default true), created_by (FK users), timestamps
- [ ] Create migration `create_sequence_steps_table`: id, sequence_id (FK nurture_sequences CASCADE), sort_order (int), channel (enum: email/sms), template_content (text), subject (string nullable for email), delay_days (int default 1), timestamps
- [ ] Create `NurtureSequenceWorkflow` extending `Workflow` (laravel-workflow): each step waits `delay_days`, then sends via appropriate channel, records engagement
- [ ] Create `EnrollContactInSequenceAction`: starts workflow for a contact
- [ ] AI-generated content: use `laravel/ai` to suggest subject lines and body copy for each step based on contact profile and property interest
- [ ] Inertia page at `/crm/sequences` for CRUD of sequences and steps
- [ ] Verify: enrolling a contact in a sequence dispatches the workflow; first step sends after delay

### US-005: GPT Cold Outreach Builder

**Status:** todo
**Priority:** 3
**Description:** Auto-suggest subject lines, CTAs, and email body copy for cold outreach.

- [ ] Create Inertia page at `/crm/outreach/builder`
- [ ] Input: target contact profile (or segment description), property/project context, tone selection
- [ ] Use `laravel/ai` agent to generate: 3 subject line variants, email body with CTA, optional A/B variant
- [ ] Save generated templates to `email_templates` or notes for reuse
- [ ] SSE streaming for real-time generation display
- [ ] Verify: entering a contact profile generates email copy with subject lines

### US-006: Landing Page AI Copy Generator

**Status:** todo
**Priority:** 3
**Description:** Generate high-converting landing page content from project/lot data.

- [ ] Create action `GenerateLandingPageCopyAction`: accepts project/lot data, uses `laravel/ai` to produce headline, hero copy, feature bullets, CTA text
- [ ] Integrate with campaign_websites form (from Step 5): "Generate with AI" button fills CKEditor sections
- [ ] Output structured JSON: { headline, hero_copy, features[], cta_text, meta_description }
- [ ] Verify: clicking "Generate with AI" on campaign website form populates content sections

### US-007: Smart Lead Routing

**Status:** todo
**Priority:** 3
**Description:** AI-driven lead-agent matching with auto-assignment rules.

- [ ] Create `LeadRoutingRule` config (stored in org settings or dedicated table): rules based on suburb, property type, agent capacity
- [ ] Create `RouteLeadAction`: evaluates rules + AI scoring to suggest or auto-assign agent_contact_id on new leads
- [ ] Show routing suggestion on contact detail (dismissible banner: "Suggested agent: John Smith")
- [ ] Optional: auto-assign if org setting `auto_route_leads = true`
- [ ] Verify: new lead gets a routing suggestion based on configured rules

### US-008: GPT Lead Brief Generator

**Status:** todo
**Priority:** 4
**Description:** Auto-generate a detailed contact profile summary from short bio and form data.

- [ ] Create `GenerateLeadBriefAction`: accepts contact_id, uses `laravel/ai` to generate a 2-3 paragraph profile from: name, form submissions, property interests, engagement history, notes
- [ ] Store generated brief in `contacts.extra_attributes` (schemaless) under `ai_brief` key
- [ ] Show AI brief on contact show page (collapsible section)
- [ ] "Regenerate Brief" button to refresh
- [ ] Verify: generating a brief for a contact with form data produces a readable profile summary

### US-009: Social Media Content Packs (Optional)

**Status:** todo
**Priority:** 5
**Description:** GPT-generated social media content for property marketing.

- [ ] Create `GenerateSocialPackAction`: accepts project/lot, generates posts for Facebook, Instagram, LinkedIn
- [ ] Output: 3-5 post variants with hashtags and image suggestions
- [ ] Display on project show page under "Marketing" tab
- [ ] Optional Canva integration placeholder (document API endpoint for future)
- [ ] Verify: generating a social pack for a project produces at least 3 post variants
