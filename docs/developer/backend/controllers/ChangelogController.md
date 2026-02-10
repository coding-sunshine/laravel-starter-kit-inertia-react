# ChangelogController

## Purpose

Serves the public changelog: paginated list of published changelog entries ordered by release date.

## Location

`app/Http/Controllers/Changelog/ChangelogController.php`

## Methods

| Method | HTTP | Route | Purpose |
|--------|------|-------|---------|
| `index` | GET | `changelog.index` | Paginated published entries, latest first |

## Routes

- `changelog.index`: GET `changelog` — Changelog listing

## Actions Used

None.

## Validation

None.

## Related Components

- **Page**: `changelog/index`
- **Model**: `ChangelogEntry`
- **Routes**: `changelog.index`
