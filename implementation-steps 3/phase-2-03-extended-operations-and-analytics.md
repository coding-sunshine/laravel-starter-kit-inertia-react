# Phase 2 Step 3: Extended operations and analytics

**Goal:** Extend operations visibility and analytics so the investor demo can show clear KPIs, trends, and drill-downs for fleet efficiency and utilization.

**Prerequisites:** Phase 2-01 and 2-02 in good shape. Dashboard and list pages working.

---

## 1. Dashboard extensions

- **KPI strip:** Ensure vehicles, drivers, trips, work orders link to the right list. Add one or two more KPIs if they tell the story (e.g. Alerts open, Compliance due soon). Keep 4-6 cards.
- **Trends:** Keep Trips and Work orders last 14 days. Optionally add Fuel spend or Defects if data exists.
- **Fleet overview and Work orders by status:** Clear empty states; optional click bar to filter list.
- **Activity:** Recent work orders, defects, compliance at risk, quick links. Add AI insights card from Phase 2-02 if not already on dashboard.
- **Performance:** All dashboard data in one or two controller calls; no N+1.

---

## 2. Operational drill-downs

- From KPI to list: one click; optional default filters (e.g. status=open).
- From chart to list: View all or click segment to list with that filter. Breadcrumbs correct.

---

## 3. Reports and report executions

- Reports list: saved reports, last run. Run report and view result; download (CSV/PDF) from execution.
- One or two hero reports for demo: e.g. Fleet utilisation summary, Compliance due next 30 days.

---

## 4. Alerts and notifications

- Alerts index: status, severity, entity; Acknowledge. Filter by status/type. Dashboard or insights link to unacknowledged alerts.
- Alert preferences: link from alerts or settings.

---

## 5. Trips and routes

- Trips list: filters (vehicle, driver, date). Show page has summary and optional See stops.
- Routes: view plus three-dot; show with stops; Optimize and Apply optimized order visible for demo.

---

## 6. Done when

- Dashboard links and optional filters correct; reports run and download; alerts clear; trips and routes consistent.
- Proceed to **phase-2-04** for compliance, safety, electrification.
