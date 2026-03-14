# AnalyticsController

## Purpose

Serves the analytics dashboard with CRM statistics and chart data, and handles natural language analytics queries via `NlAnalyticsQueryAction`.

## Location

`app/Http/Controllers/AnalyticsController.php`

## Routes

| Method | URI | Route Name | Description |
|--------|-----|------------|-------------|
| GET | `/analytics` | `analytics.index` | Render analytics dashboard |
| POST | `/analytics/nl-query` | `analytics.nl-query` | Process NL analytics query |

## Props (index)

- `stats`: totalContacts, activeSales, openTasks, settledThisMonth
- `charts`: contactsByStage, salesByStatus, reservationsByStage, tasksByType

## Related Components

- **Action**: `NlAnalyticsQueryAction`
- **Page**: `resources/js/pages/analytics/index.tsx`
