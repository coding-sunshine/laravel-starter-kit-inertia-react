# changelog/index

## Purpose

Public changelog listing: paginated published entries with version, type badge, title, description, and release date.

## Location

`resources/js/pages/changelog/index.tsx`

## Route Information

- **URL**: `changelog`
- **Route Name**: `changelog.index`
- **HTTP Method**: GET
- **Middleware**: web

## Props (from Controller)

| Prop | Type | Description |
|------|------|-------------|
| `entries` | `LengthAwarePaginator` | Paginated changelog entries (data, current_page, last_page, prev_page_url, next_page_url). Each entry: id, title, description, version, type, released_at |

## User Flow

1. User visits `changelog` (or clicks Changelog on welcome).
2. Reads entries; can paginate with Previous/Next.

## Related Components

- **Controller**: `ChangelogController@index`
- **Route**: `changelog.index`
- **Wayfinder**: `@/routes/changelog` (index)
