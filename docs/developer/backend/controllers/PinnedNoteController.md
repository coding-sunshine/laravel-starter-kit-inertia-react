# PinnedNoteController

**Location**: `app/Http/Controllers/PinnedNoteController.php`
**Last Updated**: 2026-03-15

## Routes

| Method | URI | Name | Description |
|--------|-----|------|-------------|
| GET | `reservations/{reservation}/pinned-notes` | `pinned-notes.reservation.index` | List notes for a reservation |
| POST | `reservations/{reservation}/pinned-notes` | `pinned-notes.reservation.store` | Create note for reservation |
| GET | `sales/{sale}/pinned-notes` | `pinned-notes.sale.index` | List notes for a sale |
| POST | `sales/{sale}/pinned-notes` | `pinned-notes.sale.store` | Create note for sale |
| DELETE | `pinned-notes/{pinnedNote}` | `pinned-notes.destroy` | Delete a pinned note |

## Actions Used

- `CreatePinnedNoteAction`

## Notes Returned

Only `is_active = true` notes are returned, ordered by `order` ASC. Author relation is eager loaded.

## Middleware

Requires `auth` + `verified`.
