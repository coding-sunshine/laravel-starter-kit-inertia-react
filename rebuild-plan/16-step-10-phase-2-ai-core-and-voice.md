# Step 16 (Step 10): Phase 2 — AI Core & Voice

## Goal

Deliver **Bot In A Box v2**, **Vapi.ai** integration, **AI smart summaries**, **GPT Concierge** (property matching via chat/voice), **auto-generated content** (flyers, ads, emails from listing + personas), **predictive suggestions**, and **strategy-based funnel engine** (templates, N8N, Vapi). Builds on Steps 0–15.

## Starter Kit References

- **Laravel AI SDK**: agents, tools, memory; **Prism**: ad-hoc generation
- **pgvector**: RAG for property/contact matching
- **Kit agent_conversations**: extend for CRM/website/widget contexts
- **Frontend**: `docs/developer/frontend/ai-chat.md` — chat UI for agents

## Deliverables

1. **Bot In A Box v2**: Conversational AI across CRM, websites, and lead-capture widgets; single agent config with tools (search contacts, search properties, create task).
2. **OpenAI integration**: GPT for content gen, follow-up suggestions, summarisation, rephrasing (already partially in kit; document and extend for CRM).
3. **Vapi.ai integration**: Voice call AI coaching; emotional sentiment analysis; webhook handlers and sync to CRM (call log, outcome).
4. **AI smart summaries**: Auto-summary for leads, tasks, meetings, deals (e.g. on contact/sale timeline or dashboard).
5. **GPT Concierge bot**: Matches leads to suitable properties via chat/voice; uses RAG or filters over projects/lots; optional voice front-end.
6. **Auto-generated content**: Flyers, ads, emails from listing data and personas (templates + Prism/Laravel AI).
7. **GPT predictive suggestions**: Next best actions (tags, tasks, follow-ups); expose on contact/sale/task views.
8. **Strategy-based funnel engine**: Pre-built funnels (Co-Living, Rooming, Dual Occ, etc.); funnel templates with landing pages, email sequences, lead scoring, property filters; N8N flow connector; Vapi integration for follow-up/booking; funnel analytics; strategy tags on leads/properties.

## DB Design (this step)

- **funnel_templates**, **funnel_instances**, **strategy_tags**; **call_logs** (for Vapi); **ai_summaries** (summarizable_type, summarizable_id, content, model, created_at); extend **ai_bot_*** for Bot v2 and funnel prompts.

## Data Import

- **None.**

## AI Enhancements

- All deliverables are AI/voice-driven.

## Verification (verifiable results)

- Bot responds on CRM and (if implemented) widget; Vapi call logs in CRM; summaries appear on at least one entity; Concierge suggests properties; one funnel template deploys and tracks conversions.

## Human-in-the-loop (end of step)

**STOP after this step. Do not proceed to Step 17 until the human has completed the checklist below.**

Human must:
- [ ] Confirm Bot v2 responds in CRM (and widget if implemented).
- [ ] Confirm Vapi integration (if implemented) logs calls in CRM.
- [ ] Confirm smart summaries appear on at least one entity.
- [ ] Confirm Concierge suggests properties for a lead.
- [ ] Confirm auto-generated content and predictive suggestions work.
- [ ] Confirm strategy funnel engine (or MVP) deploys and tracks.
- [ ] Approve proceeding to Step 17 (Phase 2: Property, builder & push portal).

## Acceptance Criteria

- [ ] Bot In A Box v2, Vapi integration (or documented deferral), smart summaries, Concierge, auto-generated content, predictive suggestions, and strategy funnel engine (or phased) delivered per scope.
