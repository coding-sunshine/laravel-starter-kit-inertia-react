# MemberListingsController

## Purpose

Renders the member listings page combining both project and lot DataTables.

## Location

`app/Http/Controllers/MemberListingsController.php`

## Methods

| Method | HTTP Method | Route | Purpose |
|--------|------------|-------|---------|
| `index` | GET | `/member-listings` | Return Inertia page with both LotDataTable and ProjectDataTable props |

## Routes

- `member-listings.index`: `GET /member-listings` - Displays the member listings page with projects and lots

## Actions Used

None

## Validation

None — read-only endpoint

## Related Components

- **Pages**: `resources/js/pages/member-listings/index.tsx` (rendered by this controller)
- **DataTables**: `LotDataTable`, `ProjectDataTable` (used to build inertia props)
- **Routes**: `member-listings.index` (defined in routes/web.php)
