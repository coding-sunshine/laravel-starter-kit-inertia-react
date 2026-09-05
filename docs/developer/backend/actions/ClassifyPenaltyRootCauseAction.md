# ClassifyPenaltyRootCauseAction

## Purpose

Uses AI to classify a penalty's root cause into a standardized category, determine preventability, and suggest remediation.

## Location

`app/Actions/ClassifyPenaltyRootCauseAction.php`

## Method Signature

```php
public function handle(Penalty $penalty): void
```

## Dependencies

None (no constructor dependencies).

## Parameters

| Parameter | Type | Description |
|-----------|------|-------------|
| `$penalty` | `Penalty` | The penalty to classify |

## Return Value

`void` - Updates the penalty record directly via `updateQuietly()`.

## Usage Examples

### From Observer

```php
app(ClassifyPenaltyRootCauseAction::class)->handle($penalty);
```

### From Command

```php
(new ClassifyPenaltyRootCauseAction)->handle($penalty);
```

## Related Components

- **Agent**: `RootCauseClassifierAgent` - AI agent that classifies root causes
- **Observer**: `PenaltyObserver` - Triggers classification on penalty create/update
- **Model**: `Penalty` - Updated with `root_cause_category`, `is_preventable`, `suggested_remediation`

## Notes

- Skips classification if both `root_cause` and `description` are blank
- Uses `updateQuietly()` to avoid triggering the observer recursively
- Dispatched asynchronously via the `PenaltyObserver` (queued closure)
- Categories: `equipment_failure`, `operational_delay`, `documentation_error`, `overloading`, `weather_force_majeure`, `railway_authority_issue`, `communication_gap`, `resource_shortage`
