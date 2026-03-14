# Step 17 (Step 11): Phase 2 — Property, Builder & Push Portal

## Goal

Add **builder white-label portals**, **property match intelligence**, **builder + project CRM**, **inventory API uploads**, **agent control panel** for publishing, **auto-validation & MLS formatting**, **de-duplication & versioning**, **white-labelling**, **AI push suggestions**, and **compliance integrations** (FIRB/NDIS). Builds on Steps 0–16.

## Starter Kit References

- **Filament**: Resources for builders, projects, lots; custom pages
- **DataTable**: Push history, listing versions
- **API**: Internal or public endpoints for inventory sync
- **Media**: Spatie Media Library for listing assets

## Deliverables

1. **Builder white-label portals**: Branded builder views; full stock and lead visibility; org/brand scoping.
2. **Property match intelligence**: AI filters and buyer–property match scoring; expose on contact or search.
3. **Builder + project CRM**: Pipeline for builders; contract and agent engagement tracking.
4. **Inventory API uploads**: JSON/CSV/API import for large property sync; validation and conflict handling.
5. **Agent control panel**: Control visibility per channel; schedule go-live; view push history.
6. **Auto-validation & MLS formatting**: Detect and correct incomplete or invalid listing data; format for MLS/REA/Domain if in scope.
7. **De-duplication & versioning**: Prevent listing conflicts; track changes and versions per listing.
8. **White-labelling**: Inject brand logo/contact into listing view (per org or per site).
9. **AI-powered push suggestions**: Suggest optimal time/day to publish.
10. **Compliance integrations**: Auto-check FIRB/NDIS eligibility from external providers (optional).

## DB Design (this step)

- **listing_versions** (listable_type, listable_id, version, snapshot, created_at); **push_schedules**; **builder_portals** or org settings for white-label; **match_scores** (contact_id, project_id/lot_id, score, factors).

## Data Import

- **None.**

## AI Enhancements

- Property match intelligence; AI push suggestions.

## Verification (verifiable results)

- Builder portal loads; match score visible; inventory import runs; agent panel shows channels and history; validation and versioning work; white-label appears on listing view.

## Human-in-the-loop (end of step)

**STOP after this step. Do not proceed to Step 18 until the human has completed the checklist below.**

Human must:
- [ ] Confirm builder portal loads with correct branding.
- [ ] Confirm property match score visible for at least one contact.
- [ ] Confirm inventory API/CSV import runs successfully.
- [ ] Confirm agent control panel shows channels and push history.
- [ ] Confirm validation and versioning work for listings.
- [ ] Confirm white-label displays on listing view.
- [ ] Approve proceeding to Step 18 (Phase 2: CRM enhancements & analytics).

## Acceptance Criteria

- [ ] Builder portals, property match, builder CRM, inventory API, agent control panel, validation/versioning, white-label, and optional compliance delivered per scope.
