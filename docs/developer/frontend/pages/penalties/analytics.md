# Penalty Analytics Page

## Purpose

Provides comprehensive penalty analytics with interactive charts, breakdowns by type/siding/responsibility, and AI-powered insights.

## Location

`resources/js/pages/penalties/analytics.tsx`

## Route Information

- **URL**: `/penalties/analytics`
- **Route Name**: `penalties.analytics`
- **HTTP Method**: GET
- **Middleware**: `auth`, `verified`

## Props (from Controller)

| Prop | Type | Description |
|------|------|-------------|
| `summary` | `object` | Totals: count, amount, disputed, waived, trend |
| `byType` | `array` | Penalty counts/amounts grouped by type |
| `byResponsible` | `array` | Penalties grouped by responsible party |
| `bySiding` | `array` | Penalties grouped by siding |
| `monthlyTrend` | `array` | Monthly penalty totals for trend chart |
| `byDayOfWeek` | `array` | Penalties distributed by day of week |
| `recentPenalties` | `array` | Latest penalties for drill-down table |
| `sidings` | `array` | Available sidings for filtering |
| `aiInsights` | `AiInsight[] \| null` | AI-generated recommendations (deferred) |

## User Flow

1. User navigates to `/penalties/analytics`
2. Summary cards show key metrics (total, amount, trend)
3. AI insights card loads asynchronously with recommendations
4. Charts display: monthly trend (area), by type (bar), by siding (horizontal bar), by day (bar), by responsibility (pie)
5. User can filter by siding or date range
6. Drill-down table shows recent penalties with detail links

## Related Components

- **Controller**: `PenaltyController@analytics`
- **Action**: `GeneratePenaltyInsightsAction` (deferred prop)
- **Route**: `penalties.analytics`

## Implementation Details

- Uses Recharts for all chart visualizations
- AI insights are loaded via Inertia deferred props with skeleton fallback
- Severity-colored cards (high=red, medium=amber, low=blue) for AI recommendations
- Charts are responsive with custom tooltips and formatters
