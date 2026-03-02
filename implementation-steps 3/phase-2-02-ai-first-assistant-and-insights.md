# Phase 2 Step 2: AI-first assistant and insights

**Goal:** Make the Fleet Assistant the primary way users interact with fleet data: expand tools and RAG, add proactive insights and contextual "Ask assistant" entry points across the app.

**Prerequisites:** Phase 2-01 completed. Fleet Assistant and conversation memory already working.

---

## 1. Expand assistant tools

- **Work orders:** ListWorkOrders (status, vehicle, date range), GetWorkOrder(id). Return summary and link to show.
- **Compliance:** ListComplianceItems (type, expiring_within_days). Use for "What's expiring soon?"
- **Service schedules:** ListServiceSchedules (vehicle, due_before, type). "Next service for vehicle X."
- **Defects:** ListDefects (vehicle, driver, status). "Open defects."
- **Routes:** ListRoutes, GetRoute(id) with stops.
- **Alerts:** By type and unacknowledged. "Any active alerts?"
- **Optional:** GetVehicle(id), GetDriver(id), GetTrip(id) for "Tell me about X."

Implement each tool with organization scoping; document tool names and parameters in the agent instructions.

---

## 2. RAG and document grounding

- Confirm document uploads (MOT, V5C, insurance) trigger chunking and embedding into `document_chunks`. Same embedding model; store `organization_id` and source.
- Assistant "search fleet documents" tool: query `document_chunks` by similarity, scoped to org. Return top N chunks with source. Agent instructions: cite sources when answering about documents or expiry.

---

## 3. Proactive insights

- When the user opens the assistant or dashboard, compute insights: e.g. "3 vehicles with MOT due in 14 days", "2 unacknowledged alerts". Show at top of assistant or in "Suggested questions".
- First message in new conversation can include these insights and suggested prompts.
- Optional: "AI insights" card on dashboard with 2–3 bullets and "Ask assistant" link.

Implementation: e.g. `FleetInsightsService::forOrganization($orgId)`; call from dashboard and assistant.

---

## 4. Contextual "Ask assistant" entry points

- **Vehicle show:** "Ask assistant about this vehicle" – opens assistant with context (registration, id) and optional pre-fill "When is the next service?"
- **Driver show:** Same for driver.
- **Work order show:** "Ask assistant about this work order."
- Use existing `?prompt=` and extend with `?context=vehicle:123` so the assistant receives context.

---

## 5. Done when

- Assistant has tools for work orders, compliance, service schedules, defects, routes, alerts.
- RAG cites sources; proactive insights and suggested questions appear.
- "Ask assistant about this [vehicle/driver/work order]" on show pages with pre-filled context.
- Streaming and conversation UX solid; errors handled with retry.

Proceed to **phase-2-03** for extended operations and analytics.

---

## Implementation status (complete)

- **Tools:** ListWorkOrders, ListComplianceItems, ListServiceSchedules, ListDefects, ListRoutes, ListAlerts, ListVehicles, ListDrivers, ListTrips; GetWorkOrder, GetVehicle, GetDriver, GetRoute, GetTrip; FleetDocumentSearch. All registered in FleetAssistant with org scoping.
- **RAG:** FleetDocumentSearch queries document_chunks by org; agent instructions require citing document sources.
- **Proactive insights:** `FleetInsightsService::forOrganization($orgId)` returns insights (compliance expiring in 14 days, service due, unacknowledged alerts, overdue work orders). Dashboard shows "AI insights" card when non-empty; assistant index receives insights and suggested questions and displays them when starting a new chat.
- **Contextual Ask assistant:** Work order show has "Ask assistant" linking to `/fleet/assistant?context=work_order:{id}`. Assistant supports `?context=vehicle:123`, `work_order:123`, `driver:123` and pre-fills context_prompt (e.g. "Tell me about work order ID 123"). Vehicle and driver show already had Ask assistant from P2-01.
- **Done:** Streaming/errors/retry unchanged; phase ready for phase-2-03.
