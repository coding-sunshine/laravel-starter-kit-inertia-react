# Manager Brief — Frontend

## Purpose

Presents an AI-generated shift summary for the active siding: up to 5 prioritised
action cards produced by the LLM, plus four live data widgets (exposure ticker,
operator scoreboard, pending queue, trend strip). The page polls every 15 s for
widget data without a full reload.

## Location

`resources/js/pages/manager-brief/index.tsx`

## Route Information

- **URL**: `/manager-brief`
- **Route Name**: `manager-brief.index`
- **HTTP Method**: GET
- **Middleware**: `web`, `auth`, `verified`, permission `sections.manager_brief.view`

## Props (from Controller)

| Prop | Type | Description |
|------|------|-------------|
| `actions` | `ActionCard[]` | Up to 5 AI action cards (empty while `ai_status = 'pending'`) |
| `generated_at` | `string` | ISO 8601 timestamp of last AI generation |
| `ai_status` | `'ok' \| 'pending' \| 'failed'` | Drives `StalenessBanner` and `ActionCardStack` empty state |
| `live_exposure` | `LiveExposureData` | Rs-at-stake ticker data |
| `operator_scoreboard` | `ScoreboardData` | Top/bottom operator lists |
| `pending_queue` | `PendingQueueData` | Override and dispute counts |
| `trend_strip` | `TrendStripData` | WoW deltas and sparklines |
| `can_refresh` | `boolean` | Whether the user may trigger a manual AI refresh |
| `siding` | `{ id, name, code }` | Active siding context |

## User Flow

1. User navigates to `/manager-brief` (nav entry "Manager Brief").
2. Controller resolves the user's active siding, reads the cached AI brief, and
   fetches live widget data from four services.
3. Page renders the `ActionCardStack` (may show an empty/pending state if the AI
   brief is being generated for the first time).
4. A `setInterval` triggers a partial Inertia reload every 15 s, refreshing only
   `live_exposure`, `operator_scoreboard`, `pending_queue`, `trend_strip`, and
   `widget_errors`.
5. The user can click **Refresh Now** (`RefreshNowButton`) to POST
   `manager-brief.refresh`, which queues a new AI generation and clears the cache.

## Components

All components live in `resources/js/components/manager-brief/`.

| Component | Purpose |
|-----------|---------|
| `ActionCardStack` | Renders AI action cards; handles `pending` / `failed` empty states |
| `LiveExposureTicker` | Animated rupee counter for live overload exposure |
| `OperatorScoreboard` | Top/bottom operator accuracy table |
| `PendingQueue` | Override count + dispute count widget |
| `TrendStrip` | Three WoW KPI tiles with sparklines |
| `RefreshNowButton` | Manual AI refresh trigger (POST `manager-brief.refresh`) |
| `StalenessBanner` | Shows how old the AI brief is; warns on `failed` status |
| `types.ts` | All TypeScript types matching the controller payload |

## Related Components

- **Controller**: `ManagerBriefController@index`, `ManagerBriefController@refresh`
- **Routes**: `manager-brief.index`, `manager-brief.refresh`
- **Actions**: `BuildManagerBrief`, `CollectSignals`, `RankSignals`
- **Services**: `LiveExposureCalculator`, `OperatorScoreboard`, `PendingQueue`, `TrendStrip`

## Implementation Details

- Polling uses `router.reload({ only: [...] })` from Inertia v3 — no Axios, no
  manual fetch. The `only` list restricts which props are re-sent by the server,
  keeping each poll lightweight.
- `can_refresh` is gated server-side; the button is disabled when the previous
  refresh was less than 60 s ago to prevent abuse.
- AI generation is always async (Artisan command queued via `Artisan::queue`);
  the page never blocks on the LLM response.
