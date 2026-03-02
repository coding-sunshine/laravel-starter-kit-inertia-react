# Phase 2 Step 5: Investor demo narrative and polish

**Goal:** Define the investor demo narrative, key screens, and metrics for a clear "AI-first fleet intelligence" story in under 10 minutes. Produce a one-pager and runbook.

**Prerequisites:** Phase 2-01 through 2-04 completed or in progress.

---

## 1. Narrative arc (suggested)

- **Opening:** "AI-first fleet intelligence platform: one place for operations, compliance, safety, sustainability—with a conversational AI assistant and proactive insights."
- **Dashboard:** KPIs, trends, activity; drill-down in one click.
- **Assistant:** "List my vehicles." "When is the next service for vehicle X?" Show streaming and citation. "RAG and tools; answers grounded in your data."
- **Proactive:** Show suggested questions / AI insights ("3 MOTs due soon", "2 open alerts").
- **Operations:** Vehicle show, "Ask assistant about this vehicle"; work orders; compliance at risk.
- **AI under the hood:** AI job runs / analysis results; electrification and optimization results.
- **Close:** "Single platform, AI-first; ready to scale."

---

## 2. Key screens checklist

- Fleet dashboard (KPIs, charts, activity, AI insights).
- Fleet Assistant (two queries: list + document/expiry; streaming and citation).
- Vehicles list and Vehicle show ("Ask assistant about this vehicle").
- Work orders list (one work order).
- Compliance at risk or Compliance items (expiring soon).
- AI job runs or AI analysis results (one AI outcome).
- Electrification plan or Fleet optimization (one result).

Each screen loads without errors; use seeded demo data.

---

## 3. Metrics for one-pager

- **Coverage:** 80+ fleet entities; full CRUD and workflows.
- **AI-first:** Assistant with RAG and 10+ tools; proactive insights; AI job runs (predictive maintenance, fraud, compliance); electrification and optimization.
- **UK/EU depth:** Operator licences, tachograph, vehicle checks, risk assessments, toolbox talks, permit to work, PPE, driver wellness and coaching.
- **Tech:** Laravel, Inertia/React, PostgreSQL, pgvector, Laravel AI SDK.

Save as `docs/FLEET-INVESTOR-ONEPAGER.md` or export to PDF.

---

## 4. Demo data and environment

- One demo org: 5–10 vehicles, 3–5 drivers, assignments, trips, work orders, compliance items with near-term expiry, 1–2 AI job runs. Document in seeder or docs.
- No console errors or failed requests in key flows; handle assistant rate limits and errors.

---

## 5. Done when

- Narrative and runbook agreed; key screens stable; one-pager with metrics; demo data seeded and environment stable.
- Phase 2 complete. Use **phase-2-AI-00-OVERVIEW.md** for AI deepening summary.
