# Fleet Intelligence Platform – Phase 2: Investor demo readiness

This folder contains **Phase 2** step-by-step guidance: building on the completed fleet app (all 11 phases + AI assistant in place) to extend features, deepen AI-first capabilities, improve current areas, and prepare a compelling investor demo. Each step is written so you or an AI can execute in order. Schema and migrations remain in **FLEET_FINAL_DATABASE_SCHEMA.md** in the parent folder where applicable.

**Context:** The core platform is done; there is roughly **50% time remaining** before the fleet investor demo. Phase 2 uses that window to make the product more comprehensive, more clearly AI-first, and demo-ready.

---

## How to use Phase 2

1. **Order:** Execute **phase-2-01** first (current improvements and gaps), then the remaining phase-2 steps in sequence. AI-first and investor-demo steps can be parallelized where dependencies allow.
2. **Per step:** Open the step file and follow the sections in order: Goal → Prerequisites → Tasks (with subsections) → Done when.
3. **Schema reference:** For any new tables or columns, use **FLEET_FINAL_DATABASE_SCHEMA.md**. For existing behaviour, refer to the live codebase (`app/Http/Controllers/Fleet/`, `resources/js/pages/Fleet/`).
4. **AI usage:** You can hand one step file to an AI: “Implement Phase 2 step N from implementation-steps 3/phase-2-NN-….md. Align with the existing Fleet codebase and schema.”

---

## What is already built (codebase summary)

The following is implemented and in use:

| Area | Implemented |
|------|-------------|
| **Foundation** | Locations, cost centers, drivers, trailers, vehicles, geofences, garages, fuel stations, EV charging stations, operator licences (full CRUD, Index/Create/Edit/Show). |
| **Assignments** | Driver–vehicle assignments (index, store, update, destroy); assign/unassign from vehicle and driver sides. |
| **Operations** | Routes (with stops, optimize, apply-optimized-order), trips (index, show), behavior events, telematics devices, geofence events. |
| **Fuel & maintenance** | Fuel cards, fuel transactions, service schedules, work orders (with lines and parts), defects (with run-damage-assessment). |
| **Compliance & safety** | Compliance items, driver working time, tachograph downloads; vehicle checks (templates, checks, items), risk assessments, vehicle discs, tachograph calibrations; safety policy acknowledgments, permit to work, PPE assignments, safety observations, toolbox talks. |
| **Documents & AI** | Emissions records, carbon targets, sustainability goals; AI analysis results, AI job runs (predictive maintenance, fraud detection, compliance prediction); electrification plan (index, generate), fleet optimization (index, analyze). |
| **Insurance & incidents** | Insurance policies, incidents (damage assessment, incident analysis), insurance claims (damage assessment, generate FNOL). |
| **Workflows** | Workflow definitions and executions; execute workflow. |
| **EV & training** | EV charging sessions, EV battery data; training courses, sessions, qualifications, enrollments. |
| **Costs & alerts** | Cost allocations, alerts (index, show, acknowledge), alert preferences; reports, report executions (run, download). |
| **Workshop & assets** | API integrations, API logs; dashcam clips; workshop bays, parts inventory, parts suppliers, tyre inventory, vehicle tyres; grey fleet vehicles, mileage claims, pool vehicle bookings; contractors, contractor compliance, contractor invoices. |
| **Wellness & coaching** | Driver wellness records, driver coaching plans. |
| **Fines & lifecycle** | Fines, vehicle leases, vehicle recalls, warranty claims. |
| **Extras & audit** | Parking allocations, e-lock events, axle load readings, data migration runs. |
| **Fleet UI** | Fleet dashboard (KPIs, charts, activity, quick links); Fleet Assistant (conversational AI, RAG, tools, streaming, conversations); fleet-only layout and sidebar (Dashboard, Assistant, Operations, Maintenance, Fuel & energy, Compliance & risk, Setup & locations); floating assistant FAB; view/edit/delete patterns (e.g. Vehicles, Drivers, Routes). |

**Stack:** Laravel + Inertia/React + PostgreSQL (+ pgvector for RAG where used). Tenancy and organization scoping applied across fleet. Fleet Assistant uses Laravel AI SDK (or equivalent) with conversation memory and fleet tools.

---

## Phase 2 step index

| Step | File | Focus |
|------|------|--------|
| **P2-01** | `phase-2-01-current-improvements-and-gaps.md` | Suggestions on current features (assistant, dashboard, list/detail UX, navigation, performance) and identified gaps. |
| **P2-02** | `phase-2-02-ai-first-assistant-and-insights.md` | AI-first enhancements: deeper Fleet Assistant (tools, RAG, streaming), proactive insights, natural-language entry points across the app. |
| **P2-03** | `phase-2-03-extended-operations-and-analytics.md` | Extended operations and analytics: richer dashboards, KPIs, reports, and operational visibility. |
| **P2-04** | `phase-2-04-compliance-safety-electrification.md` | Compliance, safety, and electrification: polish, automation hooks, and investor-visible differentiators. |
| **P2-05** | `phase-2-05-investor-demo-narrative-and-polish.md` | Investor demo: narrative, key screens, metrics to highlight, and one-pager. |
| **P2-AI-00** | `phase-2-AI-00-OVERVIEW.md` | AI Phase 2 overview: what to deepen (RAG, tools, agents, jobs) and how it stays AI-first. |

---

## Phase 2 principles

- **AI-first:** Every major workflow should be reachable or augmentable via the Fleet Assistant and AI-driven insights; Phase 2 deepens tools, RAG, and proactive messaging.
- **Investor-ready:** Clear narrative, consistent UX, and a small set of “hero” screens and metrics that tell the story in &lt;10 minutes.
- **Same stack and conventions:** No change to Laravel + Inertia/React, tenancy, userstamps, soft deletes, or schema conventions unless a step explicitly adds an extension.
- **Reuse over rebuild:** Prefer extending existing controllers, pages, and components over new parallel systems.

Refer to **FLEET_FINAL_DATABASE_SCHEMA.md** for schema details and to the codebase for current behaviour. Proceed to **phase-2-01** to start with current improvements and gaps.

---

## Phase 2-01 implementation status (current)

The following Phase 2-01 items have been implemented in the codebase:

| Area | Done |
|------|------|
| **Dashboard** | KPI cards link to lists (Vehicles, Drivers, Trips, Work orders); "Ask assistant" link on Expiring compliance activity card; quick links include Assistant. |
| **Work orders list** | View (eye) + three-dot (Edit, Delete); Delete uses Dialog; no duplicate View/Edit. |
| **Trips list** | View (eye) icon to trip show page. |
| **Vehicle show** | "Ask assistant" and "Edit" buttons in header; prompt pre-fills context (vehicle registration, ID, next service). |
| **Driver show** | "Ask assistant" and "Edit" buttons in header; prompt pre-fills context (driver name, ID, assignments/compliance). |
| **Routes list** | View (eye) + three-dot (Edit, Delete); Delete uses confirmation Dialog (no raw `confirm()`). |
| **Fleet Assistant tools** | Added `ListWorkOrders` and `ListComplianceItems`; agent instructions updated; work orders and compliance (incl. expiring soon) answerable via assistant. |
| **Performance** | Dashboard uses single controller payload; recent work orders/defects eager-load `vehicle`. |

Remaining for full Phase 2-01 polish: conversation delete already uses Dialog; optional "Ask assistant" on more activity cards; broader list-page audit (e.g. Defects, Compliance) for view + three-dot pattern; N+1 audit on other index/show controllers as needed.
