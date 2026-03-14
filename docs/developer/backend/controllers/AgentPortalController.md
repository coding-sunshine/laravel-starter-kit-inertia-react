# AgentPortalController

## Purpose

Agent control panel for managing push schedules and viewing push history across channels.

## Location

`app/Http/Controllers/AgentPortalController.php`

## Methods

| Method | HTTP Method | Route | Purpose |
|--------|------------|-------|---------|
| index | GET | `/agent-portal` | Show push history and scheduled pushes |
| schedule | POST | `/agent-portal/schedule` | Schedule a new listing push |

## Routes

- `agent-portal.index`: `GET /agent-portal`
- `agent-portal.schedule`: `POST /agent-portal/schedule`
