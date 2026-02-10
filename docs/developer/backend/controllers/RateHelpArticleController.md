# RateHelpArticleController

## Purpose

Invokable controller that records user feedback (helpful / not helpful) for a help article and redirects back with a status message.

## Location

`app/Http/Controllers/HelpCenter/RateHelpArticleController.php`

## Methods

| Method | HTTP | Route | Purpose |
|--------|------|-------|---------|
| `__invoke` | POST | `help.rate` | Validate `is_helpful`, call RateHelpArticleAction, redirect back |

## Routes

- `help.rate`: POST `help/{helpArticle:slug}/rate` — Submit rating (no auth required)

## Actions Used

- `RateHelpArticleAction` — Increment `helpful_count` or `not_helpful_count`

## Validation

Inline: `is_helpful` required boolean.

## Related Components

- **Action**: `RateHelpArticleAction`
- **Model**: `HelpArticle`
- **Routes**: `help.rate`
- **Page**: `help/show` (form posts here)
