# HelpCenterController

## Purpose

Serves the public help center: index with featured articles and articles grouped by category; single article view with related articles and view count increment.

## Location

`app/Http/Controllers/HelpCenter/HelpCenterController.php`

## Methods

| Method | HTTP | Route | Purpose |
|--------|------|-------|---------|
| `index` | GET | `help.index` | Featured articles + articles by category |
| `show` | GET | `help.show` | Single article by slug; 404 if not published; increments views; related articles |

## Routes

- `help.index`: GET `help` — Help center index
- `help.show`: GET `help/{helpArticle:slug}` — Single article (slug binding)

## Actions Used

None.

## Validation

None (route model binding only).

## Related Components

- **Pages**: `help/index`, `help/show`
- **Model**: `HelpArticle`
- **Routes**: `help.index`, `help.show`
