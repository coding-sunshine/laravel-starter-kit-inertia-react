# Step 24 (Step 18): Phase 2 — R&D & Special (Geanelle's List)

## Goal

Deliver **AI-generated suburb & state info** for projects (price, rent from REA or provider), **AI extraction of photos/floor plans from brochures** into Project Profile, **email-to-builders** from CRM (templates: price list/availability, more info, hold request, property request), and **Resemble.ai Voice Cloning** for personalised AI voice agents. Optional: Joey's/Geanelle's suggestions when R&D is ready. Builds on Steps 0–23.

## Starter Kit References

- **Prism / Laravel AI**: Structured output for suburb data; image analysis or document extraction if using vision API
- **Mail**: Laravel mail + templates for builder emails
- **Media**: Attach extracted images to project/lot
- **Saloon**: HTTP integration for Resemble.ai API

## Deliverables

1. **AI suburb & state info**: For a project (or suburb), call external source (e.g. REA) or AI to fetch/generate price and rent data; store or display on project profile.
2. **AI brochure extraction**: Upload brochure (PDF/image); extract facade photo, floor plans, etc. via AI; add to project/lot media collections.
3. **Email to builders**: CRM action to send email to builder (contact or org); templates: Request Price list/Availability, More info, Hold request, Property request; track sent in activity or mail_log.
4. **Resemble.ai Voice Cloning** *(R&D)*: Integrate [Resemble.ai](https://www.resemble.ai) API via Saloon `ResembleConnector`. Clone an agent or BDM voice from audio samples; store voice UUID per user/org. Inject cloned voice into:
   - **Bot In A Box v2** (Step 16) TTS responses
   - **Vapi.ai flows** (Step 16) as a custom voice provider
   - **Cold outreach** (Step 15) personalised voice messages

   Implementation notes:
   - `App\Http\Integrations\Resemble\ResembleConnector` (Saloon)
   - Actions: `CreateVoice`, `GenerateSpeech`, `GetVoice`
   - Store `resemble_voice_uuid` on `users` or org settings (schemaless)
   - Env: `RESEMBLE_API_KEY`, `RESEMBLE_PROJECT_UUID`
   - Fallback: if no cloned voice, fall back to Vapi default TTS voice

5. **Joey's / Geanelle's suggestions**: Placeholder or integration point for R&D; document when user testing or API is available.

## DB Design (this step)

- **suburb_data** (suburb, state, source, price_rent_json, fetched_at); optional **builder_email_templates**; use activity_log or **mail_log** for sent builder emails.
- Extend `users` or org settings (schemaless `extra_attributes`) with `resemble_voice_uuid` (nullable string).
- No new top-level table required for Resemble.ai — voice metadata lives on user/org.

## Data Import

- **None.**

## AI Enhancements

- AI suburb/state data; AI brochure extraction; Resemble.ai voice cloning for personalised outreach.

## Verification (verifiable results)

- Fetch suburb data for a project; upload brochure and see extracted images on project; send builder email with each template type; generate a speech sample via Resemble.ai API and confirm audio returned.

## Human-in-the-loop (end of step)

**End of Phase 2. Human must confirm rebuild and Phase 2 scope complete or list follow-ups.**

Human must:
- [ ] Confirm AI suburb/state info displays for at least one project.
- [ ] Confirm brochure extraction adds images to project/lot.
- [ ] Confirm builder email templates send correctly.
- [ ] Confirm Resemble.ai connector can generate speech (or note if API key not yet available — defer voice cloning to post-launch).
- [ ] Confirm R&D placeholders (Joey's/Geanelle's) documented.
- [ ] Sign off on Phase 2 completion or list remaining follow-ups.

## Acceptance Criteria

- [ ] AI suburb/state info, AI brochure extraction, email-to-builders templates, Resemble.ai voice integration (or documented deferral), and R&D suggestions delivered per scope.
