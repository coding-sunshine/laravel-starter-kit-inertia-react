# Railway Rake Management Control System (RRMCS) – Full Feature Overview

This document describes all railway-specific features in the application, including core operations, reports, AI/MCP integration, and configuration.

---

## 1. Overview

**RRMCS** (Railway Rake Management Control System) covers:

- **Sidings** (e.g. Pakur, Dumka, Kurwa) and user–siding access
- **Indents** – rake demand/orders with e-Demand reference and confirmation PDFs
- **Rakes** – lifecycle, TXR (placement), weighments, guard inspection
- **Railway receipts (RR)** – document capture, parsing (wagon table, FNR, freight, charges), storage
- **Weighments** – in-motion weighment slips and PDF attachments
- **Road dispatch** – vehicle arrivals and unloads (road-to-rail linkage)
- **Penalties & demurrage** – calculation, breakdown, alerts
- **Reconciliation** – rake-level and power-plant receipts
- **Reports** – predefined report keys and CSV/PDF-style exports
- **Dashboard & alerts** – executive KPIs and demurrage/operational alerts
- **Mobile** – siding dashboard for field use
- **AI / MCP** – tools for sidings and user–siding access for AI clients

---

## 2. Sidings and Access

- **Sidings** are railway loading points (e.g. Pakur, Dumka, Kurwa) with `name`, `code`, `location`, `station_code`, `is_active`.
- **User–siding access**: users are linked to sidings (with optional *primary* siding). Access is enforced by:
  - **Middleware**: `siding.access` (`EnsureSidingAccess`) on routes that need siding-scoped data.
  - **Policies**: e.g. `IndentPolicy`, `RakePolicy` use siding access for view/update.
- **Super-admin** can see all sidings; other users only see their assigned sidings.
- **Config**: `config/rrmcs.php` (e.g. `imwb_default_siding_code` for load-sensor import).

**Docs**: Permissions (see [developer/backend/permissions](../developer/backend/permissions.md)).

---

## 3. Indents

- **Purpose**: Rake demand/order per siding – indent number, target quantity (MT), state (e.g. pending, acknowledged), dates, optional e-Demand reference ID and FNR number.
- **e-Demand confirmation PDF**: Indent can have one PDF in the `indent_confirmation_pdf` media collection (Spatie Media). Upload on create/update; “View confirmation (PDF)” on show.
- **CRUD**: List (`indents.index`), create (`indents.create` / `indents.store`), show (`indents.show`), edit (`indents.edit` / `indents.update`). Form uses `forceFormData` for file upload.
- **Action**: `CreateIndent` – creates indent with siding and optional PDF.

**Docs**: [indents/index](../developer/frontend/pages/indents/index.md), [indents/create](../developer/frontend/pages/indents/create.md), [indents/show](../developer/frontend/pages/indents/show.md), [indents/edit](../developer/frontend/pages/indents/edit.md), [CreateIndent](../developer/backend/actions/createindent.md).

---

## 4. Rakes

- **Lifecycle**: Rakes belong to a siding and progress through states (e.g. placed, loading, loaded, dispatched). Linked to indents where applicable.
- **TXR (placement)**: Update placement/TXR for a rake via `rakes.txr.update` (PUT `rakes/{rake}/txr`).
- **Weighments**: Per-rake weighment records; create via `rakes.weighments.store` (POST `rakes/{rake}/weighments`). Weighment can have a **weighment slip PDF** in the `weighment_slip_pdf` collection; “View slip” on rake show.
- **Guard inspection**: Record guard inspection for a rake via `rakes.guard-inspection.store`.
- **Actions**: `CreateRake`, `ProcessWeighmentDocument` (optional parsing), etc.

**Docs**: [rakes/index](../developer/frontend/pages/rakes/index.md), [rakes/show](../developer/frontend/pages/rakes/show.md).

---

## 5. Railway Receipts (RR Documents)

- **Purpose**: Store and parse railway receipt documents – FNR, freight, charges, and a **wagon table** in `rr_details` (JSON).
- **CRUD**: List, create, show, update (`railway-receipts.index`, `.create`, `.store`, `.show`, `.update`). Create/update can include PDF upload; RR can have multiple PDFs in a media collection.
- **Processing**: `ProcessRrDocument` parses uploaded RR PDF/text and populates header fields plus `rr_details.wagons`. `ReconcileRrData` uses this structure (e.g. Point 3 reconciliation with wagon data).
- **Reference PDFs**: Seeders can attach sample RR PDFs (e.g. Kurwa, Dumka) from PRD/reference folders for demo data.

**Docs**: [railway-receipts/index](../developer/frontend/pages/railway-receipts/index.md), [railway-receipts/create](../developer/frontend/pages/railway-receipts/create.md), [railway-receipts/show](../developer/frontend/pages/railway-receipts/show.md), [ProcessRrDocument](../developer/backend/actions/processrrdocument.md).

---

## 6. Weighments

- **In-motion weighment**: Weight data per rake; optional **weighment slip PDF** on the weighment model (HasMedia, `weighment_slip_pdf`).
- **UI**: Add weighment (and optional PDF) from rake show; “View slip” when a slip is attached.
- **Reports**: Weighment data report key `weighment`; `loader_vs_weighment` for overload analysis.

**Docs**: Rake show page, reports spec.

---

## 7. Road Dispatch

- **Vehicle arrivals**: Record road vehicle arrivals at sidings – `road-dispatch.arrivals.index`, `.create`, `.store`. Action: `CreateVehicleArrival`.
- **Unloads**: Record and confirm vehicle unloads – `road-dispatch.unloads.index`, `.create`, `.store`, and `road-dispatch.unloads.confirm` (PUT). Action: `ConfirmVehicleUnload`.
- **Link**: Feeds into stock/coal movement and can be used in reports (e.g. vehicle arrival, stock movement).

**Docs**: [road-dispatch/arrivals](../developer/frontend/pages/road-dispatch/arrivals/index.md), [road-dispatch/unloads](../developer/frontend/pages/road-dispatch/unloads/index.md), [CreateVehicleArrival](../developer/backend/actions/createvehiclearrival.md).

---

## 8. Penalties and Demurrage

- **Demurrage**: Charged when rakes exceed free time. Formula: `hours_over × weight_mt × demurrage_rate_per_mt_hour`. Rate configured in `config/rrmcs.php` (`demurrage_rate_per_mt_hour`) and env `RRMCS_DEMURRAGE_RATE_PER_MT_HOUR`.
- **Penalties**: Stored per rake/siding with **calculation breakdown** (e.g. component-wise) for transparency. Penalty register report: `penalty_register`.
- **Action**: `CalculateDemurrageCharges` – computes demurrage for applicable rakes.
- **Alerts**: `SyncDemurrageAlertsAction` creates/updates alerts for demurrage; dashboard shows alerts.

**Docs**: [CalculateDemurrageCharges](../developer/backend/actions/calculatedemurragecharges.md), [penalties/index](../developer/frontend/pages/penalties/index.md), [reports-spec](../developer/backend/reports-spec.md).

---

## 9. Reconciliation

- **Rake reconciliation**: View reconciliation status per rake – `reconciliation.index`, `reconciliation.show` (per rake). Uses RR and related data; `ReconcileRrData` (and RR wagon data) supports reconciliation logic.
- **Power-plant receipts**: List, create, store – `reconciliation.power-plant-receipts.index`, `.create`, `.store`. For matching receipt data with railway/coal movement.

**Docs**: [reconciliation/index](../developer/frontend/pages/reconciliation/index.md), [reconciliation/show](../developer/frontend/pages/reconciliation/show.md), [ReconcileRakeAction](../developer/backend/actions/reconcilerakeaction.md).

---

## 10. Reports

- **Report keys** (from `RunReportAction`): `siding_coal_receipt`, `rake_indent`, `txr`, `unfit_wagon`, `wagon_loading`, `weighment`, `loader_vs_weighment`, `rake_movement`, `rr_summary`, `penalty_register`.
- **UI**: `reports.index` – user selects report key, siding, and date range; POST `reports/generate` returns JSON or CSV (with `export_csv=1`).
- **GenerateReports**: Additional narrative-style outputs (daily ops, stock movement, rake lifecycle, demurrage analysis, indent fulfillment, vehicle arrival, penalties & reconciliation, performance metrics, financial impact, compliance & audit). Can be mapped to draft Excel tabs.
- **Action**: `RunReportAction` (implements handlers per key), `GenerateReports` (PDF/Excel-style).

**Docs**: [reports-spec](../developer/backend/reports-spec.md), [reports/index](../developer/frontend/pages/reports/index.md), [RunReportAction](../developer/backend/actions/runreportaction.md).

---

## 11. Alerts

- **Demurrage and operational alerts**: Stored in `alerts`; synced by `SyncDemurrageAlertsAction` (e.g. from siding-scoped penalty/demurrage data).
- **UI**: `alerts.index` – list alerts; `alerts.resolve` (PUT) to mark resolved.

**Docs**: [alerts/index](../developer/frontend/pages/alerts/index.md).

---

## 12. Executive Dashboard

- **Controller**: `ExecutiveDashboardController` (GET `dashboard`).
- **Data (siding-scoped)**: Summary (rakes by state, total rakes, penalties this month, indents pending/acknowledged, vehicles received today), siding-wise coal stock, active rakes, alerts, penalty chart data, siding list.
- **Refreshes**: Demurrage alerts are synced on each dashboard load.

**Docs**: [dashboard](../developer/frontend/pages/dashboard.md).

---

## 13. Mobile – Siding Dashboard

- **Page**: `resources/js/pages/mobile/SidingDashboard.tsx` – mobile-optimised view for a siding (e.g. key metrics, quick actions). Siding context may come from route or user’s primary siding.

**Docs**: [mobile/SidingDashboard](../developer/frontend/pages/mobile/SidingDashboard.md).

---

## 14. AI and MCP (Railway-Relevant)

- **MCP server**: `App\Mcp\Servers\ApiServer` – “API capabilities for the Railway Rake Management Control System (RRMCS)”. Endpoint: `POST /mcp/api`; auth: Sanctum.
- **Railway-specific MCP tools**:
  - **sidings_index**: List all railway sidings (id, name, code, location, station_code, is_active).
  - **user_sidings**: Get sidings a user can access, with role and primary siding (param: `user_id`).
- **Other tools on same server**: `users_index`, `users_show` (user listing and detail for support/ops context).
- **Chat**: Application chat (`chat.conversations.index`, `chat.conversations.show`, `chat.message`) can be used for conversational support; AI agents (Laravel AI SDK) and Prism are available for agents, embeddings, and tool-calling. Railway context can be provided via MCP tools (e.g. sidings, user sidings) when the chat or an agent calls the MCP server.

**Docs**: [MCP](../developer/backend/mcp.md), [Laravel AI SDK](../developer/backend/ai-sdk.md), [ChatController](../developer/backend/controllers/chatcontroller.md).

---

## 15. Configuration and Data Import

- **config/rrmcs.php**:
  - **Demurrage**: `demurrage_rate_per_mt_hour` (env: `RRMCS_DEMURRAGE_RATE_PER_MT_HOUR`).
  - **PRD / real data import**: `prd_import` – paths for Excel files used by `RealDataImportSeeder` (e.g. Pakur monthly, Dumka/Kurwa loading, IMWB sensor). Optional `reports_draft` path for report mapping.
  - **IMWB default siding**: `imwb_default_siding_code` (env: `RRMCS_IMWB_DEFAULT_SIDING`) when siding cannot be determined from import.
- **RealDataImportSeeder**: Uses `ImportRakeDataFromExcelAction` and files from `config/rrmcs.php` `prd_import` to seed historical rake/penalty data.
- **Reference PDFs**: Seeders attach sample RR/weighment/indent PDFs from PRD/reference paths for demos.

**Docs**: [saloon](../developer/backend/saloon.md) (external APIs), [ImportRakeDataFromExcelAction](../developer/backend/actions/importrakedatafromexcelaction.md).

---

## 16. External APIs (Future)

- **Saloon** is used for third-party HTTP integrations. For RRMCS, future connectors (e.g. FBD e-Demand, RR status, weighment-vendor APIs) will live under `App\Http\Integrations\{Name}\`; base URL and keys in `config/services.php` and `.env`. Called from Actions or jobs.

**Docs**: [saloon](../developer/backend/saloon.md).

---

## 17. Summary Table

| Area            | Main features                                                                 | Key routes / actions                                      |
|-----------------|-------------------------------------------------------------------------------|-----------------------------------------------------------|
| Sidings & access| Siding CRUD, user–siding, EnsureSidingAccess, super-admin sees all           | Policies, middleware                                      |
| Indents         | CRUD, e-Demand ref, FNR, confirmation PDF                                    | indents.*, CreateIndent                                   |
| Rakes           | Lifecycle, TXR, weighments, guard inspection, slip PDF                       | rakes.*, CreateRake                                       |
| Railway receipts| RR CRUD, parsing (wagon table, FNR, freight, charges)                         | railway-receipts.*, ProcessRrDocument, ReconcileRrData    |
| Weighments      | Slip PDF, reports                                                            | rakes.weighments.store                                    |
| Road dispatch   | Vehicle arrivals, unloads, confirm                                           | road-dispatch.*, CreateVehicleArrival, ConfirmVehicleUnload |
| Penalties       | Demurrage rate, calculation breakdown, alerts                               | penalties.index, CalculateDemurrageCharges                 |
| Reconciliation  | Rake-level, power-plant receipts                                             | reconciliation.*, ReconcileRakeAction                      |
| Reports         | 10 report keys, GenerateReports, CSV export                                 | reports.index, reports.generate, RunReportAction          |
| Dashboard       | Summary, stocks, active rakes, alerts, penalty chart                        | dashboard (ExecutiveDashboardController)                 |
| Mobile          | Siding dashboard                                                             | mobile SidingDashboard                                    |
| AI / MCP        | sidings_index, user_sidings, chat                                            | MCP ApiServer, /mcp/api                                   |
| Config / import | rrmcs.php, demurrage, prd_import, RealDataImportSeeder                      | config/rrmcs.php                                         |
