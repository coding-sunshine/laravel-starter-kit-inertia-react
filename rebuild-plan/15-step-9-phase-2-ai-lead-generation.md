# Step 15 (Step 9): Phase 2 — AI Lead Generation & Outreach

## Goal

Add **AI-driven lead generation and outreach** features: multi-channel capture, nurture sequences, cold outreach builder, landing page AI, campaign optimisation, lead scoring & routing, social content, lead brief generator, and optional coaching/voice. Builds on Steps 0–14; no MySQL import.

## Starter Kit References

- **Prism**: `docs/developer/backend/prism.md` — `ai()` helper, OpenRouter
- **Laravel AI SDK**: `docs/developer/backend/ai-sdk.md` — agents, embeddings
- **Actions**: Single-action classes for sending sequences, scoring, routing
- **Queue**: Laravel queue for scheduled nurture steps

## Deliverables

1. **Multi-channel lead capture engine**: Accept leads via forms, phone, SMS, chat, voice; normalize to Contact + contact_emails/contact_phones; store channel and source (campaign, ad, agent).
2. **Auto-nurture sequences**: GPT-generated email/SMS/voice drip campaigns + scheduling. Use **laravel-workflow/laravel-workflow** (Durable Workflow) for multi-day sequences that wait days between steps (resumable, crash-safe). See 00-kit-package-alignment.md.
3. **GPT cold outreach builder**: Auto-suggest subject lines, CTAs, body copy for emails; store templates; optional A/B variants.
4. **Landing page AI copy generator**: Generate high-converting landing page content from listing (project/lot) data via Prism/Laravel AI.
5. **AI campaign optimisation**: Learn from engagement (opens, clicks, conversions) to suggest or apply improvements; store engagement events and feed to model or rules.
6. **Smart lead score & routing**: AI-driven prioritisation and lead–agent matching; auto-assignment rules; expose score on contact and in lists.
7. **Social Media In A Box v2**: GPT content packs; Canva integration; engagement analytics (optional).
8. **GPT lead brief generator**: Auto-generate detailed contact profile from short bio + form data.
9. **GPT coaching layer (optional)**: Real-time prompts, objection handling, FAQs for sales agents.
10. **Resemble.ai / voice cloning (optional)**: Custom voice agents in chat/voice bots; R&D scope.

## DB Design (this step)

- **lead_scores** (contact_id, score, factors_json, updated_at); **engagement_events** (contact_id, event_type, source, payload, created_at); **nurture_sequences** (id, name, steps json, is_active); **sequence_steps** (sequence_id, order, channel, template_ref, delay_days); **campaigns** / **ads** if not present; **contact_attributions** extended per Step 14.

## Data Import

- **None.** New functionality only.

## AI Enhancements

- All deliverables above are AI-enhanced (GPT for copy, scoring, routing, optimisation).

## Verification (verifiable results)

- Manual: create a lead via two channels; run a nurture step; generate cold outreach copy; generate landing page copy from a project; view lead score and routing suggestion.

## Human-in-the-loop (end of step)

**STOP after this step. Do not proceed to Step 16 until the human has completed the checklist below.**

Human must:
- [ ] Confirm multi-channel capture creates contacts with attribution.
- [ ] Confirm at least one nurture sequence runs on schedule.
- [ ] Confirm cold outreach and landing page AI generators produce valid output.
- [ ] Confirm lead score and routing (or rules) visible on contacts.
- [ ] Approve proceeding to Step 16 (Phase 2: AI core & voice).

## Acceptance Criteria

- [ ] Multi-channel capture creates contacts with attribution.
- [ ] Nurture sequences run on schedule; cold outreach and landing page AI generators work.
- [ ] Lead score and routing (or rules) in place; optional social/coaching/voice scoped and documented.
