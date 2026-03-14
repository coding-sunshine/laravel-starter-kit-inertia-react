# EnrollInNurtureSequenceAction

## Purpose

Enrolls a contact in a nurture sequence, creating a SequenceEnrollment and starting the durable NurtureSequenceWorkflow.

## Location

`app/Actions/EnrollInNurtureSequenceAction.php`

## Method Signature

```php
public function handle(Contact $contact, NurtureSequence $sequence): SequenceEnrollment
```

## Dependencies

None

## Return Value

Returns the created or updated `SequenceEnrollment` model.

## Related Components

- **Workflow**: `App\Workflows\NurtureSequenceWorkflow`
- **Controller**: `NurtureSequenceController`
