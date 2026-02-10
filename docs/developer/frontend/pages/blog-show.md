# blog/show

## Purpose

Single blog post view: title, date, author, and content (HTML). Back link to blog index.

## Location

`resources/js/pages/blog/show.tsx`

## Route Information

- **URL**: `blog/{post:slug}`
- **Route Name**: `blog.show`
- **HTTP Method**: GET
- **Middleware**: web

## Props (from Controller)

| Prop | Type | Description |
|------|------|-------------|
| `post` | `Post` | id, title, slug, excerpt, content, published_at, author |

## User Flow

1. User lands from blog index or direct URL.
2. Reads post content (rendered via dangerouslySetInnerHTML).
3. Can click "Back to blog" to return to index.

## Related Components

- **Controller**: `BlogController@show`
- **Route**: `blog.show`
- **Wayfinder**: `@/routes/blog` (index, show)
