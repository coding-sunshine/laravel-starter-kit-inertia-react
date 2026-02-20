# Railway (RRMCS) documentation

## Full feature overview

**[rrmcs-features.md](./rrmcs-features.md)** – Single document describing all railway-specific features: sidings, indents, rakes, railway receipts, weighments, road dispatch, penalties & demurrage, reconciliation, reports, dashboard, alerts, mobile siding dashboard, AI/MCP tools, config, and external APIs.

---

## List of docs that relate to railway / RRMCS

All documentation that is railway/rake/siding/indent/weighment/RR/penalty/demurrage/reports related:

### Railway feature doc (this folder)

| Doc | Description |
|-----|-------------|
| [railway/rrmcs-features.md](./rrmcs-features.md) | Full RRMCS feature overview (all features including AI) |

### Frontend pages (Inertia/React)

| Doc | Description |
|-----|-------------|
| [developer/frontend/pages/indents/index.md](../developer/frontend/pages/indents/index.md) | Indents list |
| [developer/frontend/pages/indents/create.md](../developer/frontend/pages/indents/create.md) | Create indent |
| [developer/frontend/pages/indents/show.md](../developer/frontend/pages/indents/show.md) | Indent detail |
| [developer/frontend/pages/indents/edit.md](../developer/frontend/pages/indents/edit.md) | Edit indent |
| [developer/frontend/pages/rakes/index.md](../developer/frontend/pages/rakes/index.md) | Rakes list |
| [developer/frontend/pages/rakes/show.md](../developer/frontend/pages/rakes/show.md) | Rake detail (TXR, weighments, guard inspection, slip PDF) |
| [developer/frontend/pages/railway-receipts/index.md](../developer/frontend/pages/railway-receipts/index.md) | Railway receipts list |
| [developer/frontend/pages/railway-receipts/create.md](../developer/frontend/pages/railway-receipts/create.md) | Create RR |
| [developer/frontend/pages/railway-receipts/show.md](../developer/frontend/pages/railway-receipts/show.md) | RR detail |
| [developer/frontend/pages/penalties/index.md](../developer/frontend/pages/penalties/index.md) | Penalties list |
| [developer/frontend/pages/reports/index.md](../developer/frontend/pages/reports/index.md) | Reports (siding, date range, export) |
| [developer/frontend/pages/reconciliation/index.md](../developer/frontend/pages/reconciliation/index.md) | Reconciliation list |
| [developer/frontend/pages/reconciliation/show.md](../developer/frontend/pages/reconciliation/show.md) | Rake reconciliation |
| [developer/frontend/pages/reconciliation/power-plant-receipts/index.md](../developer/frontend/pages/reconciliation/power-plant-receipts/index.md) | Power-plant receipts list |
| [developer/frontend/pages/reconciliation/power-plant-receipts/create.md](../developer/frontend/pages/reconciliation/power-plant-receipts/create.md) | Create power-plant receipt |
| [developer/frontend/pages/road-dispatch/arrivals/index.md](../developer/frontend/pages/road-dispatch/arrivals/index.md) | Vehicle arrivals |
| [developer/frontend/pages/road-dispatch/arrivals/create.md](../developer/frontend/pages/road-dispatch/arrivals/create.md) | Create arrival |
| [developer/frontend/pages/road-dispatch/unloads/index.md](../developer/frontend/pages/road-dispatch/unloads/index.md) | Unloads list |
| [developer/frontend/pages/road-dispatch/unloads/create.md](../developer/frontend/pages/road-dispatch/unloads/create.md) | Create unload |
| [developer/frontend/pages/dashboard.md](../developer/frontend/pages/dashboard.md) | Executive dashboard (rakes, penalties, indents, stocks, alerts) |
| [developer/frontend/pages/alerts/index.md](../developer/frontend/pages/alerts/index.md) | Alerts (demurrage/operational) |
| [developer/frontend/pages/mobile/SidingDashboard.md](../developer/frontend/pages/mobile/SidingDashboard.md) | Mobile siding dashboard |

### Backend – Actions

| Doc | Description |
|-----|-------------|
| [developer/backend/actions/createindent.md](../developer/backend/actions/createindent.md) | CreateIndent |
| [developer/backend/actions/calculatedemurragecharges.md](../developer/backend/actions/calculatedemurragecharges.md) | CalculateDemurrageCharges |
| [developer/backend/actions/processrrdocument.md](../developer/backend/actions/processrrdocument.md) | ProcessRrDocument (RR parsing, wagon table) |
| [developer/backend/actions/reconcilerakeaction.md](../developer/backend/actions/reconcilerakeaction.md) | ReconcileRakeAction |
| [developer/backend/actions/importrakedatafromexcelaction.md](../developer/backend/actions/importrakedatafromexcelaction.md) | ImportRakeDataFromExcelAction (PRD Excel import) |
| [developer/backend/actions/runreportaction.md](../developer/backend/actions/runreportaction.md) | RunReportAction (report keys, CSV) |
| [developer/backend/actions/createvehiclearrival.md](../developer/backend/actions/createvehiclearrival.md) | CreateVehicleArrival |
| [developer/backend/actions/confirmvehicleunload.md](../developer/backend/actions/confirmvehicleunload.md) | ConfirmVehicleUnload |
| [developer/backend/actions/updatestockledger.md](../developer/backend/actions/updatestockledger.md) | UpdateStockLedger |
| [developer/backend/actions/processguardinspection.md](../developer/backend/actions/processguardinspection.md) | ProcessGuardInspection |
| [developer/backend/actions/optimizeperformance.md](../developer/backend/actions/optimizeperformance.md) | OptimizePerformance (if used for railway flows) |

### Backend – Reports & config

| Doc | Description |
|-----|-------------|
| [developer/backend/reports-spec.md](../developer/backend/reports-spec.md) | Report keys, GenerateReports, UI/export |
| [developer/backend/saloon.md](../developer/backend/saloon.md) | HTTP client; RRMCS/future external APIs (FBD, weighment vendor) |

### AI / MCP (railway-relevant)

| Doc | Description |
|-----|-------------|
| [developer/backend/mcp.md](../developer/backend/mcp.md) | MCP server; railway tools: sidings_index, user_sidings |
| [developer/backend/ai-sdk.md](../developer/backend/ai-sdk.md) | Laravel AI SDK (agents, embeddings; chat can use railway context) |
| [developer/backend/controllers/chatcontroller.md](../developer/backend/controllers/chatcontroller.md) | Chat (conversations, message); can integrate with MCP for railway data |

### Other references

| Doc | Description |
|-----|-------------|
| [developer/backend/media-library.md](../developer/backend/media-library.md) | Spatie Media (RR PDFs, weighment slip PDF, indent confirmation PDF) |
| [developer/backend/userstamps.md](../developer/backend/userstamps.md) | created_by/updated_by (used on railway models where applicable) |
| Config | `config/rrmcs.php` – demurrage rate, prd_import paths, IMWB default siding (no separate doc; see rrmcs-features.md) |

---

## Quick links from repo root

- **Full feature doc**: `docs/railway/rrmcs-features.md`
- **This index**: `docs/railway/README.md`
