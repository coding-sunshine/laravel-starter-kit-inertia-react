# AddMentionAction

## Purpose

Creates a mention record and sends a `MentionNotification` to the mentioned user. Called by `CrmNoteObserver` when `@username` patterns are detected in note content.

## Location

`app/Actions/AddMentionAction.php`

## Method Signature

```php
public function handle(
    string $context,
    int $mentionableId,
    string $mentionableType,
    int $mentionedUserId,
    int $mentionedByUserId,
    int $organizationId,
): Mention
```

## Dependencies

None (uses Eloquent directly + Notification dispatch).

## Return Value

Returns the created `Mention` model instance.

## Related Components

- **Observer**: `CrmNoteObserver`
- **Notification**: `MentionNotification`
- **Model**: `Mention`
