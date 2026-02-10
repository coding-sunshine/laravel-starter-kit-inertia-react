# help/index

## Purpose

Public help center index: featured articles block and articles grouped by category with links to single article view.

## Location

`resources/js/pages/help/index.tsx`

## Route Information

- **URL**: `help`
- **Route Name**: `help.index`
- **HTTP Method**: GET
- **Middleware**: web

## Props (from Controller)

| Prop | Type | Description |
|------|------|-------------|
| `featured` | `HelpArticle[]` | Published featured articles (up to 6) |
| `byCategory` | `Record<string, HelpArticle[]>` | Published articles grouped by category key |

## User Flow

1. User visits `help` (or clicks Help on welcome).
2. Sees featured articles and categories; clicks an article to open help/show.

## Related Components

- **Controller**: `HelpCenterController@index`
- **Route**: `help.index`
- **Wayfinder**: `@/routes/help` (index, show)
