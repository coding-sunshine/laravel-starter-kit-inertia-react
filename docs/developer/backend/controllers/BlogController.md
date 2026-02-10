# BlogController

## Purpose

Serves the public blog: list of published posts (paginated) and single post view (with view count increment).

## Location

`app/Http/Controllers/Blog/BlogController.php`

## Methods

| Method | HTTP | Route | Purpose |
|--------|------|-------|---------|
| `index` | GET | `blog.index` | Paginated published posts with author |
| `show` | GET | `blog.show` | Single post by slug; 404 if not published; increments views |

## Routes

- `blog.index`: GET `blog` — Blog listing
- `blog.show`: GET `blog/{post:slug}` — Single post (slug binding)

## Actions Used

None.

## Validation

None (route model binding only).

## Related Components

- **Pages**: `blog/index`, `blog/show`
- **Model**: `Post`
- **Routes**: `blog.index`, `blog.show`
