# PublicSiteController

## Purpose

Public-facing campaign site and survey pages — no authentication required.

## Location

`app/Http/Controllers/PublicSiteController.php`

## Methods

| Method | HTTP Method | Route | Purpose |
|--------|------------|-------|---------|
| show | GET | `/w/{uuid}` | Public campaign site |
| survey | GET | `/survey/{uuid}` | Survey form |
| submitSurvey | POST | `/survey/{uuid}` | Submit survey → create Contact |

## Routes

- `public.campaign-site`: `GET /w/{uuid}`
- `public.survey`: `GET /survey/{uuid}`
- `public.survey.submit`: `POST /survey/{uuid}`
