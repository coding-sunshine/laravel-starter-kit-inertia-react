# PRD 11: AI Core & Voice

## Overview

> **Phase 2** — Requires AI agents from Step 6.

Deliver Bot In A Box v2 (conversational AI across CRM/websites/widgets), Vapi.ai voice integration, AI smart summaries, GPT Concierge for property matching, auto-generated content (flyers, ads, emails), predictive suggestions (next best actions), and strategy-based funnel engine. Builds on Phase 1 + PRDs 09-10.

**Prerequisites:** PRDs 00-10 complete (search, lead scoring, engagement tracking, nurture sequences working).

## Technical Context

- **AI:** `laravel/ai` (agents, tools, memory) + `prism-php/prism` + `prism-php/relay` for provider routing
- **pgvector:** RAG for property/contact matching (embeddings from Step 6)
- **Chat UI:** `docs/developer/frontend/ai-chat.md` pattern from starter kit
- **Existing:** `agent_conversations` table, `ai_bot_*` tables (from Step 5), `model_embeddings` with `HasEmbeddings` trait (Step 6)
- **Module:** `modules/module-crm/`, namespace `Cogneiss\ModuleCrm`
- **Saloon:** For Vapi.ai HTTP integration (`app/Http/Integrations/`)

### Required Environment Variables
Before starting this PRD, verify these are set in `.env`:
- `OPENAI_API_KEY` — Required for AI agents, smart summaries, property matching, and content generation
- `THESYS_API_KEY` — Optional: Thesys C1 generative UI for rich AI responses

**If any required key is missing, ASK the user before proceeding. Do not skip, stub, or use placeholder values.**

## User Stories

### US-001: Bot In A Box v2 — CRM Agent

**Status:** todo
**Priority:** 1
**Description:** Extend the existing Bot In A Box (Step 5) into a unified conversational AI with CRM tools.

- [ ] Create `CrmAssistantAgent` in `modules/module-crm/src/Ai/Agents/` extending the kit's agent pattern
- [ ] Register CRM tools: `SearchContactsTool`, `SearchPropertiesTool`, `CreateTaskTool`, `GetContactDetailTool`, `GetSalesPipelineTool`
- [ ] Agent uses `laravel/ai` with tool calling; context from `CrmContextTool` (today's tasks, recent contacts, active reservations)
- [ ] Chat UI at `/crm/assistant` using the starter kit's chat component pattern
- [ ] Conversation history stored in `agent_conversations` table (existing)
- [ ] Org-scoped: agent only accesses data within current user's organization
- [ ] Verify: asking "Show me overdue tasks" in chat returns relevant tasks from DB

### US-002: Bot In A Box v2 — Website Widget

**Status:** todo
**Priority:** 2
**Description:** Deploy the conversational AI as a website widget for lead capture.

- [ ] Create embeddable widget endpoint at `/api/crm/widget/chat` (public, rate-limited)
- [ ] Widget agent has limited tools: `SearchPropertiesTool`, `CaptureLeadTool` (creates contact from conversation)
- [ ] Widget config per org: welcome message, brand colors, enabled/disabled (stored in org settings)
- [ ] Widget serves as JS snippet embeddable on subscriber websites
- [ ] Conversations from widget create engagement events
- [ ] Verify: widget endpoint accepts chat message and returns AI response; new lead created from conversation

### US-003: Vapi.ai Voice Integration

**Status:** todo
**Priority:** 2
**Description:** Integrate Vapi.ai for AI voice coaching, sentiment analysis, and call logging.

- [ ] Create `VapiConnector` in `app/Http/Integrations/Vapi/` using Saloon
- [ ] Webhook handler at `/api/webhooks/vapi` for call events (start, end, transcript)
- [ ] Create migration `create_call_logs_table`: id, organization_id (FK), contact_id (FK contacts nullable), vapi_call_id (string), direction (inbound/outbound), duration_seconds (int), transcript (text nullable), sentiment (string nullable), outcome (string nullable), agent_user_id (FK users nullable), created_at
- [ ] Create `Cogneiss\ModuleCrm\Models\CallLog` with `BelongsToOrganization`
- [ ] Sync call outcomes to contact timeline (activity_log or notes)
- [ ] Show call logs on contact show page
- [ ] Env: `VAPI_API_KEY`, `VAPI_WEBHOOK_SECRET`
- [ ] Verify: Vapi webhook creates call log linked to contact; transcript stored

### US-004: AI Smart Summaries

**Status:** todo
**Priority:** 2
**Description:** Auto-generate summaries for contacts, sales, tasks, and meetings.

- [ ] Create migration `create_ai_summaries_table`: id, summarizable_type (string), summarizable_id (bigint), organization_id (FK), content (text), model_used (string), created_at
- [ ] Create `Cogneiss\ModuleCrm\Models\AiSummary` (morphTo summarizable)
- [ ] Create `GenerateSummaryAction`: uses `laravel/ai` to summarize entity's recent activity (last 10 notes, tasks, emails, calls)
- [ ] Auto-generate on contact show page load if no recent summary (cache 24h)
- [ ] Show summary card on contact timeline and sale detail
- [ ] "Refresh Summary" button for manual regeneration
- [ ] Verify: contact show page displays AI summary based on notes and activity

### US-005: GPT Concierge — Property Matching

**Status:** todo
**Priority:** 3
**Description:** Match leads to suitable properties via chat/voice using RAG over projects/lots.

- [ ] Create `PropertyMatchAgent` in `modules/module-crm/src/Ai/Agents/`: uses pgvector embeddings to find matching properties based on buyer preferences
- [ ] Tools: `SearchPropertyByPreferenceTool` (budget, suburb, bedrooms, type), `GetLotDetailTool`
- [ ] Input: contact preferences (from property_searches, finance_assessments, or chat input)
- [ ] Output: ranked list of matching projects/lots with reasons
- [ ] Accessible via CRM chat (`/crm/assistant`) and widget
- [ ] Show match results on contact profile under "Property Matches" section
- [ ] Verify: asking "Find properties under $500k in Brisbane" returns relevant lots with scores

### US-006: Auto-Generated Content

**Status:** todo
**Priority:** 3
**Description:** Generate flyers, ads, and emails from listing data and personas.

- [ ] Create `GenerateListingContentAction`: accepts project/lot + content_type (flyer/ad/email) + persona (investor/first-home-buyer/downsizer)
- [ ] Uses `laravel/ai` with structured output to generate content appropriate for content type
- [ ] Flyer content feeds into existing FlyrEditor (Puck) as pre-filled blocks
- [ ] Ad content generates Facebook/Google ad copy variants
- [ ] Email content generates property showcase email body
- [ ] Verify: generating flyer content for a project returns structured blocks; ad copy generates 3 variants

### US-007: GPT Predictive Suggestions

**Status:** todo
**Priority:** 3
**Description:** Surface next best actions on contact, sale, and task views.

- [ ] Create `PredictNextActionAction`: analyzes contact stage, recent activity, engagement events, and suggests 1-3 actions (e.g., "Follow up by phone", "Send property shortlist", "Schedule meeting")
- [ ] Show suggestions as dismissible banner or card on contact show page
- [ ] Show on sale detail: "Recommend: send contract reminder" based on days in stage
- [ ] Suggestions use `laravel/ai` with contact context
- [ ] User can dismiss or act on suggestion (acting creates the task/email)
- [ ] Verify: contact with no recent activity shows "Follow up" suggestion; dismissing removes it

### US-008: Strategy-Based Funnel Engine

**Status:** todo
**Priority:** 4
**Description:** Pre-built marketing funnels with landing pages, email sequences, lead scoring, and property filters.

- [ ] Create migration `create_funnel_templates_table`: id, name, slug, strategy_type (co-living/rooming/dual-occ/smsf/general), description (text), landing_page_config (jsonb), email_sequence_config (jsonb), lead_scoring_rules (jsonb), property_filters (jsonb), is_active (boolean), created_at, updated_at
- [ ] Create migration `create_funnel_instances_table`: id, organization_id (FK), funnel_template_id (FK), name, config_overrides (jsonb), leads_count (int default 0), conversions_count (int default 0), is_active (boolean), created_at, updated_at
- [ ] Create `Cogneiss\ModuleCrm\Models\FunnelTemplate` and `FunnelInstance`
- [ ] Seed 3-5 pre-built funnel templates (Co-Living, Rooming House, Dual Occ, SMSF, General)
- [ ] Deploy funnel: creates campaign website + nurture sequence + scoring rules from template
- [ ] Funnel analytics: leads count, conversion count, displayed on funnel instance detail
- [ ] Verify: deploying a funnel template creates associated campaign website and sequence; analytics show counts
