# blog/index

## Purpose

Public blog listing: paginated published posts with title, excerpt, date, and author. Links to single post view.

## Location

`resources/js/pages/blog/index.tsx`

## Route Information

- **URL**: `blog`
- **Route Name**: `blog.index`
- **HTTP Method**: GET
- **Middleware**: web

## Props (from Controller)

| Prop | Type | Description |
|------|------|-------------|
| `posts` | `LengthAwarePaginator` | Paginated posts (data, current_page, last_page, prev_page_url, next_page_url, links). Each post: id, title, slug, excerpt, published_at, author |

## User Flow

1. User visits `blog` (or clicks Blog on welcome).
2. Sees list of posts; can click a post to go to `blog/show`.
3. Can use Previous/Next for pagination.

## Related Components

- **Controller**: `BlogController@index`
- **Route**: `blog.index`
- **Wayfinder**: `@/routes/blog` (index, show)
