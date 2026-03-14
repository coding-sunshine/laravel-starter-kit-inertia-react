# BatchUpdateUsersAction

## Purpose

Updates a single column for multiple users in bulk. Only allows updates to an explicit allowlist of safe columns.

## Location

`app/Actions/BatchUpdateUsersAction.php`

## Method Signature

```php
public function handle(array $ids, string $column, mixed $value): int
```

## Dependencies

None

## Parameters

| Parameter | Type | Description |
|-----------|------|-------------|
| `$ids` | `array<int>` | Array of user IDs to update |
| `$column` | `string` | Column to update (must be in `ALLOWED_COLUMNS`) |
| `$value` | `mixed` | Value to set for the column |

## Return Value

Returns the number of users updated. Returns `0` if the column is not in the allowlist.

## Constants

```php
const array ALLOWED_COLUMNS = ['name', 'onboarding_completed'];
```

## Usage Examples

### From Controller

```php
app(BatchUpdateUsersAction::class)->handle([1, 2, 3], 'onboarding_completed', true);
```

## Related Components

- **Controller**: `UsersTableController`

## Notes

- Wraps updates in a `DB::transaction()` for atomicity.
- Columns not in `ALLOWED_COLUMNS` are silently ignored (returns 0).
- `onboarding_completed` values are cast to boolean; all other allowed columns are cast to string.
