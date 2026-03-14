# Step 19 (Step 13): Phase 2 — Marketing & Content Tools

## Goal

Add **GPT ad & social templates**, **dynamic brochure builder v2** (AI content, templates), **retargeting ad builder** (Facebook/Instagram), **email campaigns + GPT personalisation**, and **landing page generator**. Builds on Steps 0–18.

## Starter Kit References

- **Prism / Laravel AI**: Copy generation
- **Filament**: Brochure and campaign UIs
- **Existing**: mail_lists, brochure_mail_jobs, campaign_websites, flyers, flyer_templates

## Deliverables

1. **GPT ad & social templates**: Tailored for channel, persona, tone; store and select templates.
2. **Dynamic brochure builder v2**: AI content fill; templated layout selection; use existing flyers/flyer_templates.
3. **Retargeting ad builder**: Facebook/Instagram funnel-based ad creation; audience from CRM segments.
4. **Email campaigns + GPT personalisation**: Auto subject lines; dynamic body content; use or extend mail_lists and sequences. Reference **backstage/laravel-mails** webhook setup (e.g. `php artisan mail:webhooks mailgun`) for bounce/delivery tracking. See 00-kit-package-alignment.md.
5. **Landing page generator**: GPT or template-driven landing pages; link to campaigns.

## DB Design (this step)

- **ad_templates**, **brochure_layouts**; extend **campaigns** / **ads** if needed; **landing_page_templates**.

## Data Import

- **None.**

## AI Enhancements

- GPT for ad/social copy; AI content fill for brochures; GPT personalisation for email; landing page generation.

## Verification (verifiable results)

- Generate ad copy; create brochure with AI content; configure retargeting campaign; send personalised email campaign; generate landing page.

## Human-in-the-loop (end of step)

**STOP after this step. Do not proceed to Step 20 until the human has completed the checklist below.**

Human must:
- [ ] Confirm GPT ad/social templates produce valid output.
- [ ] Confirm brochure builder v2 creates brochure with AI content.
- [ ] Confirm retargeting ad builder configures campaign (or integrates with FB/IG).
- [ ] Confirm email campaign with GPT personalisation sends.
- [ ] Confirm landing page generator produces a page.
- [ ] Approve proceeding to Step 20 (Phase 2: Deal tracker enhancements).

## Acceptance Criteria

- [ ] GPT ad/social templates, brochure builder v2, retargeting ad builder, email campaigns + GPT, and landing page generator delivered per scope.
