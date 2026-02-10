# help/show

## Purpose

Single help article view: title, excerpt, content (HTML), "Was this helpful?" (Yes/No forms posting to help.rate), and related articles.

## Location

`resources/js/pages/help/show.tsx`

## Route Information

- **URL**: `help/{helpArticle:slug}`
- **Route Name**: `help.show` (GET), `help.rate` (POST)
- **HTTP Method**: GET (page), POST (rate)
- **Middleware**: web

## Props (from Controller)

| Prop | Type | Description |
|------|------|-------------|
| `article` | `HelpArticle` | id, title, slug, excerpt, content, category |
| `related` | `HelpArticle[]` | Published articles in same category (up to 5) |
| `flash.status` | `string` | Set after rating (e.g. "Thank you for your feedback.") |

## User Flow

1. User lands from help index or direct URL.
2. Reads article; can click Yes or No for "Was this helpful?" (POST to help.rate).
3. After rating, flash message is shown; can click related articles or "Back to help".

## Related Components

- **Controller**: `HelpCenterController@show`, `RateHelpArticleController`
- **Action**: `RateHelpArticleAction`
- **Routes**: `help.show`, `help.rate`
- **Wayfinder**: `@/routes/help` (index, show, rate)
