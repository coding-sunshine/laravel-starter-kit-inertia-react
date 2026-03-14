# CreatePinnedNoteAction

**Location**: `app/Actions/CreatePinnedNoteAction.php`
**Last Updated**: 2026-03-15

## Purpose

Creates a `PinnedNote` record polymorphically attached to any noteable model (e.g., `PropertyReservation`, `Sale`). Automatically sets `author_id` from the authenticated user and calculates the next `order` value.

## Signature

```php
public function handle(Model $noteable, string $content, array $roleVisibility = []): PinnedNote
```

## Parameters

| Parameter | Type | Description |
|-----------|------|-------------|
| `$noteable` | `Illuminate\Database\Eloquent\Model` | The model to attach the note to (must have `organization_id`) |
| `$content` | `string` | The note text content |
| `$roleVisibility` | `array` | Optional list of role names that can see this note |

## Returns

`PinnedNote` — the newly created record.

## Usage

```php
$action = new CreatePinnedNoteAction();
$note = $action->handle($reservation, 'Important: buyer requires special finance conditions.', ['admin', 'sales']);
```

## Related

- Model: `App\Models\PinnedNote`
- Controller: `App\Http\Controllers\PinnedNoteController`
- Routes: `pinned-notes.reservation.store`, `pinned-notes.sale.store`
