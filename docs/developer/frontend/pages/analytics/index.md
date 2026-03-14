# Analytics Page

## Location

`resources/js/pages/analytics/index.tsx`

## Route

`GET /analytics` — name: `analytics.index`

## Purpose

CRM analytics dashboard showing key statistics, bar charts for contact stages and sales pipeline, and an AI-powered natural language query interface.

## Props

| Prop | Type | Description |
|------|------|-------------|
| `stats` | `Stats` | Aggregate counts: totalContacts, activeSales, openTasks, settledThisMonth |
| `charts` | `Charts` | Data for bar charts: contactsByStage, salesByStatus, reservationsByStage, tasksByType |

## Features

- 4-stat grid with icons
- Inline bar chart components (no third-party charting lib)
- NL query section: POST to `/analytics/nl-query`, shows AI answer
- Loading spinner during query
- Pan analytics: `analytics-tab`
