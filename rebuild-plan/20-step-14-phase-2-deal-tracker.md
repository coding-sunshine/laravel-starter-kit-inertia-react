# Step 20 (Step 14): Phase 2 — Deal Tracker Enhancements

## Goal

Add **Kanban pipeline board** for Reservations/Sales (drag-to-move cards between stages), **document vault** (per-deal PDFs, emails, contracts), **payment tracker** (EOI → Deposit → Commission → Payout), and **important notes panel** (pinned alerts for Admin/Agents/Support). Builds on Steps 0–19.

## Starter Kit References

- **Spatie Media**: Attach to Sale (or deal) for document vault
- **Filament / Inertia**: Payment stages widget; pinned notes component

## Deliverables

1. **Kanban pipeline board**: `/deal-tracker` page showing Reservations/Sales as a drag-and-drop Kanban board grouped by stage (Enquiry → Qualified → Reservation → Contract → Unconditional → Settled). Use the **DataTable built-in Kanban layout** (`layout: 'kanban'` on ReservationDataTable) — do NOT build a custom Kanban. Dragging a card between columns triggers a stage update via the existing `HasToggle`-style endpoint. Cards show: contact name, project+lot, price, days in stage, agent avatar.

2. **Document vault**: Per-deal storage for PDFs, emails, contracts; media collection per Sale (or deal entity); permissions by role.

3. **Payment tracker**: Log stages (EOI, Deposit, Commission, Payout); display on deal/sale view; optional dates and amounts per stage.

4. **Important notes panel**: Pinned alerts visible to Admin, Agents, Support; store in notes with flag or separate **pinned_notes** / **important_notes**; show on dashboard or deal view.

## DB Design (this step)

- No new tables for Kanban — it renders from the existing `reservations`/`sales` tables via DataTable Kanban layout. **payment_stages** (sale_id, stage, amount, date, notes) or JSON on sales; **pinned_notes** (note_id or noteable + noteable_id, role_visibility, order). Document vault can use Spatie media with collection name per deal.

## Data Import

- **None.**

## AI Enhancements

- None required.

## Verification (verifiable results)

- `/deal-tracker` loads with Kanban columns (Enquiry → Settled); drag a card to move stage. Upload document to deal; view in vault. Set payment stages and see on deal. Create pinned note and see in panel.

## Human-in-the-loop (end of step)

**STOP after this step. Do not proceed to Step 21 until the human has completed the checklist below.**

Human must:
- [ ] Confirm `/deal-tracker` Kanban board renders with correct stage columns and cards are draggable.
- [ ] Confirm document vault stores and displays documents per deal.
- [ ] Confirm payment tracker shows stages on deal/sale view.
- [ ] Confirm important notes panel displays pinned alerts for correct roles.
- [ ] Approve proceeding to Step 21 (Phase 2: Websites & API).

## Acceptance Criteria

- [ ] Kanban pipeline board at `/deal-tracker` using DataTable built-in Kanban layout.
- [ ] Document vault, payment tracker, and important notes panel delivered per scope.
