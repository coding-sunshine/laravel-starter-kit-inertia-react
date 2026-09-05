# ADR-004: Loadrite Live Ingestion — Deferred to v2

## Status

Proposed (deferred). Sets direction; no implementation in this ADR's PR.

## Context

The Control Room dashboard (ADR-003 sibling, `/control-room`) renders live wagon-level loading state from the `wagon_loading` table. As of 2026-05-03 the table has 6,166 rows of which **0** have `loadrite_weight_mt` populated — every row is operator-entered (`weight_source = 'manual'`).

The Loadrite Cloud API (OpenAPI spec at `loadrite-cloud-api.json`) provides true real-time scale data via these endpoints:

- `/api/v2/Loading` — per-load weight + product (transactional)
- `/api/v2/NewWeight` — live weight ticker
- `/api/v2/Conveyor` — conveyor belt state (not currently visualized)
- `/api/v2/Haul`, `/api/v2/Positions` — haul/truck telemetry
- `/api/v2/Downtime` (v2/v3) — loader idle classification
- `/api/v2/context/*` — site/scale/product master data

The Saloon connector is already in place (`App\Http\Integrations\Loadrite\LoadriteConnector` + 8 request classes). Credentials per siding live in `loadrite_settings` (currently 0 rows).

What is **not** in place:
- A scheduled poller that calls Loadrite endpoints, transforms payloads, and upserts into our DB.
- Mapping from Loadrite `scaleId` → our `loaders.id`.
- Reconciliation between operator-entered values and Loadrite-pushed values.
- Backfill / replay strategy for missed events when the poller is offline.
- Onboarding UX: how a siding administrator configures Loadrite credentials and maps scales to loaders.

The Control Room v1 was deliberately built to be **source-agnostic** for `wagon_loading` weight values: `loaded_quantity_mt` is the unified weight, regardless of provenance. `weight_source` and `loadrite_weight_mt` columns already exist on `wagon_loading` so that when Loadrite data arrives, no UI changes are required.

## Decision

**Defer the Loadrite live-ingestion implementation to a separate PR.** The Control Room v1 ships using operator-entered data only.

When ready to implement, follow this phased plan:

### Phase 1 — Onboarding & credentials
- Filament form for `loadrite_settings`: per-siding API token entry, refresh-token flow, expiry display, manual-refresh button.
- Health-check button that calls `/api/v2/context/get-health-check` and surfaces the result.
- Encrypt access/refresh tokens at rest (use `encrypted` cast).

### Phase 2 — Scale-to-loader mapping
- Add `loaders.loadrite_scale_id` column (nullable string, indexed).
- Filament UI on the loader detail page: pick a Loadrite scale from `/api/v2/context/get-scales` results.
- Validation: every Loadrite scale belongs to exactly one loader (no duplicates).

### Phase 3 — Poller job
- `App\Jobs\PollLoadriteLoadingEventsJob` — scheduled every N seconds (start 30s, evaluate). Per active siding, paginate `/api/v2/Loading` since the last successful poll high-water mark.
- Idempotent upsert keyed on Loadrite event id into a new `loadrite_loading_events` audit table.
- After upsert, reconcile to `wagon_loading`: match the event's `scaleId` → loader → look up the rake currently being loaded at that siding → match wagon by sequence (or operator-set explicit mapping). On match, populate `loaded_quantity_mt`, `loadrite_weight_mt`, `weight_source = 'loadrite'`, `loadrite_last_synced_at`, then dispatch `RakeWagonLoadingUpdated` (already broadcasts on `siding.{id}` channel for the Control Room).
- On any unmatched event, queue a per-siding "needs operator attention" alert.

### Phase 4 — NewWeight live ticker
- A second poller for `/api/v2/NewWeight` to power a "current weighbridge reading" sub-tile in the Control Room. Lower priority — only useful for visual interest, not state correctness.

### Phase 5 — Reconciliation UI
- When the same wagon has both an operator-entered `loaded_quantity_mt` and a `loadrite_weight_mt`, show both side-by-side in the wagon detail. If divergence exceeds a threshold (e.g. > 0.5 MT), flag and require operator confirmation. The `loadrite_override` boolean on `wagon_loading` already exists for this purpose.

### Phase 6 — Backfill & replay
- When the poller has been offline > N hours, on resume: fetch `/api/v2/Loading` paginated all the way back to the high-water mark (Loadrite caps at 31 days). Process in chunks; do not block the foreground worker.

### Phase 7 — Conveyor + Downtime visualization
- Add Conveyor and Downtime panels to the Control Room. Out of scope for v1 because the reference mockups don't include them.

## Consequences

### Easier
- Control Room ships *now* without waiting on the entire Loadrite plumbing — operators see the same dashboard whether weights come from the entry form or from Loadrite.
- Each phase is independently shippable and reversible.
- Reconciliation logic (Phase 5) doesn't need to exist before Loadrite turns on, since `weight_source` already discriminates.

### Harder
- Until Phase 3 lands, the dashboard's "live" feel is only as live as operator entry rate. This was the user's explicit choice (Q3 = "DB-broadcast only" for v1).
- Phase 3's reconciliation logic is non-trivial — wagon matching by sequence assumes a stable order. If a rake's wagons are re-ordered mid-load, the matching breaks. Need to think hard about explicit scale → wagon mapping for high-volume sidings.
- Loadrite cache TTL is 10 minutes (per the OpenAPI spec), so requests within the same 10-min window may serve stale results. The poller's effective freshness is bounded by this. Document it in the Loadrite settings UI so operators don't expect sub-minute realtime from cloud-sourced data.

## Open questions to resolve before Phase 3

- Polling interval: 30s (chatty, hits cache often) vs 60s (matches Loadrite cache TTL boundary) vs 10min (avoids cache duplication entirely). Tradeoff: animation responsiveness vs API call cost vs stale-window size. Default proposal: 30s; will revisit once we have observability.
- Credential rotation: API tokens have 3/6/12-month durations. Auto-refresh is documented in the OpenAPI; do we trust the auto-refresh or notify operators N days before expiry? Default proposal: auto-refresh + notify 14 days out.
- Multi-tenant key handling: `loadrite_settings` is per-siding, but a single siding can have multiple Loadrite scales and they may use different credentials. Confirm with operations whether one siding always uses one credential pair.
