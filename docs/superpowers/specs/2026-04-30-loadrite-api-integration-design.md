# Work Stream 2: Loadrite API Integration

**Date:** 2026-04-30  
**Approach:** B — Saloon Connector + Separated Horizon Jobs (30s polling)  
**Status:** Approved for implementation

---

## Overview

Integrate with the Loadrite InsightHQ REST API (v2/v3) to:
1. Pull per-bucket `NewWeight` events every 30 seconds for active loading sidings
2. Sync device-measured weights into `WagonLoading` records
3. Fire real-time overload alerts (warning at 90% CC, critical at 100%+ CC) via Reverb, SMS/WhatsApp, and push notifications

**Scope:** Dumka railway siding initially. Architecture supports any number of sidings without code changes.

---

## API Details

- **Base URL:** `https://apicloud.loadrite-myinsighthq.com`
- **Auth:** Bearer JWT (access token + refresh token)
- **Architecture:** Polling only — no webhooks
- **Key endpoints used:**
  - `POST /api/v2/auth/refresh-token` — token refresh
  - `GET /api/v2/NewWeight?from={ISO8601}` — per-bucket weight events
  - `GET /api/v2/Loading?from={ISO8601}` — completed load totals
  - `GET /api/v2/context` — sites, scales, operators

---

## Saloon Integration

```
App\Http\Integrations\Loadrite\
  LoadriteConnector
  Requests\
    GetNewWeightEventsRequest
    GetLoadingEventsRequest
    RefreshTokenRequest
    GetContextRequest
```

**`LoadriteConnector`:** base URL, bearer token header, Saloon middleware for auto-refresh on 401.

**`LoadriteTokenManager` service:** reads/writes `loadrite_settings` table. On 401 → calls `RefreshTokenRequest` → stores new `access_token`, `refresh_token`, `expires_at`. Injected into connector via Saloon middleware.

### `loadrite_settings` table (new migration)

| Column | Type | Notes |
|---|---|---|
| `id` | bigint PK | |
| `siding_id` | bigint FK | nullable — NULL = global |
| `access_token` | text | encrypted via `cast: 'encrypted'` |
| `refresh_token` | text | encrypted |
| `expires_at` | datetime | |
| `created_at` / `updated_at` | timestamps | |

---

## Polling Architecture

### `PollLoadriteJob($sidingId)`

Self-scheduling Horizon job. Flow:

1. Acquire Redis lock `loadrite:polling:{siding_id}` (skip if already held)
2. Read cursor from Redis: `loadrite:cursor:{siding_id}` (default: `now() - 1 hour` on first run)
3. Call `GetNewWeightEventsRequest` with `from = cursor`
4. For each event returned:
   - Dispatch `SyncLoadriteWeightJob($event, $sidingId)` on `loadrite-sync` queue
   - Dispatch `EvaluateOverloadAlertJob($event, $sidingId)` on `loadrite-alerts` queue
5. Update cursor to timestamp of last event processed
6. Dispatch self with `->delay(now()->addSeconds(30))`

### Watchdog command: `loadrite:start-polling`

Runs on deploy and via Laravel Scheduler every 5 minutes. Dispatches `PollLoadriteJob` per configured siding if not already running (checked via Redis lock existence). No-op if already polling.

**Multi-siding:** add a row to `loadrite_settings` for each new siding. The watchdog dispatches one `PollLoadriteJob` per active siding automatically.

---

## Weight Sync

### `WagonLoading` schema changes (new migration)

| New column | Type | Default | Notes |
|---|---|---|---|
| `loadrite_weight_mt` | decimal(8,3) | null | Device-measured cumulative weight |
| `weight_source` | enum | `'manual'` | `manual` / `loadrite` / `weighbridge` |
| `loadrite_last_synced_at` | datetime | null | Timestamp of last Loadrite update |
| `loadrite_override` | boolean | false | True when operator has manually overridden a Loadrite-sourced weight |

### `SyncLoadriteWeightJob($event, $sidingId)`

**Matching:** find active `Rake` at `$sidingId` → match `WagonLoading` by `wagon_sequence` from event `Sequence` field.

**Update rules:**

| Current `weight_source` | Action |
|---|---|
| `manual` (not explicitly overridden) | Update `loadrite_weight_mt`, set `weight_source = 'loadrite'` |
| `loadrite` | Update `loadrite_weight_mt` (running total as buckets added) |
| `manual` (explicitly overridden by operator) | Update `loadrite_weight_mt` only — `weight_source` stays `'manual'`, shown side-by-side |
| `weighbridge` | No-op — weighbridge is always final |

**No match found:** log warning with event details, skip. Do not create orphan records.

An operator "explicit override" is detected via a new boolean `loadrite_override` column on `WagonLoading` (set true when operator manually edits a Loadrite-sourced record).

---

## Alert Logic

### `EvaluateOverloadAlertJob($event, $sidingId)`

```
wagon_cc      = Wagon.cc_mt (or WagonLoading.carrying_capacity_mt)
current_weight = event weight (cumulative)
percentage    = current_weight / wagon_cc × 100

90% ≤ percentage < 100%  → WARNING
percentage ≥ 100%        → CRITICAL
```

**Debounce:** Redis TTL key `loadrite:alert:{wagon_id}:{level}` (5-minute TTL). Skip if key exists — prevents alert spam during continuous loading.

**Alert delivery — three channels via new `LoadriteOverloadNotification` (follows `DemurrageAlertNotification` pattern):**

1. **Reverb broadcast** — `WagonOverloadWarning` / `WagonOverloadCritical` Broadcastable event → real-time toast on dashboard for active users
2. **Database** — stored in Laravel `notifications` table (same as `DemurrageAlertNotification`) → visible in notification centre
3. **SMS/WhatsApp + push** — added as additional channels in `LoadriteOverloadNotification::via()` once SMS/push channels are wired up in the app

**Alert record stored with:**
```json
{
  "type": "overload_warning|overload_critical",
  "wagon_id": 123,
  "wagon_number": "BOXN-45678",
  "siding_id": 2,
  "weight_mt": 68.4,
  "cc_mt": 68.0,
  "percentage": 100.6,
  "level": "critical",
  "source": "loadrite"
}
```

---

## Token Setup (one-time)

Before going live, generate an API token in InsightHQ Home → API → Tokens (token life: maximum available, refresh: automatic). Store in `loadrite_settings` via an Artisan command `loadrite:store-token` that accepts the token interactively and encrypts it.

---

## Implementation Order

1. Migration — `loadrite_settings` table
2. Migration — add `loadrite_weight_mt`, `weight_source`, `loadrite_last_synced_at`, `loadrite_override` to `wagon_loadings`
3. `LoadriteConnector` + all Saloon requests
4. `LoadriteTokenManager` service
5. `PollLoadriteJob`
6. `SyncLoadriteWeightJob`
7. `EvaluateOverloadAlertJob`
8. `WagonOverloadWarning` + `WagonOverloadCritical` broadcast events
9. `loadrite:start-polling` command + scheduler registration
10. `loadrite:store-token` command (interactive token setup)
11. Pest tests
12. Generate token in InsightHQ, store via command, enable on Dumka

---

## Tests (Pest)

### 1. Unit — `LoadriteTokenManager`
- Expired token → assert `RefreshTokenRequest` called → new token written to `loadrite_settings`
- Valid token → assert no refresh call made

### 2. Unit — `EvaluateOverloadAlertJob`
- 89% CC → no alert dispatched
- 90% CC → warning alert dispatched, Redis debounce key set
- Second call within 5 min → no duplicate alert (debounce)
- 100%+ CC → critical alert dispatched
- `weight_source = 'weighbridge'` wagon → no alert (loading complete)

### 3. Feature — `SyncLoadriteWeightJob`
- Seed rake + wagon loading + Loadrite event → assert `loadrite_weight_mt` updated, `weight_source = 'loadrite'`
- `weight_source = 'weighbridge'` record → assert NOT overwritten
- No matching wagon → assert warning logged, no DB write

### 4. Feature — `PollLoadriteJob`
- Mock Saloon HTTP → assert Redis cursor updated → assert child jobs dispatched with correct payloads
- Already-held Redis lock → assert job exits immediately (no duplicate polling)
