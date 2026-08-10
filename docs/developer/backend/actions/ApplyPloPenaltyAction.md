# ApplyPloPenaltyAction

## Purpose

{One-line description of what this action does}

## Location

`app/Actions/ApplyPloPenaltyAction.php`

## Method Signature

```php
public function handle({parameters}): {returnType}
```

## Dependencies

{List injected dependencies from constructor, or "None" if no dependencies}

## Parameters

| Parameter | Type   | Description   |
| --------- | ------ | ------------- |
| {param}   | {type} | {description} |

## Return Value

{Description of what the method returns}

## Usage Examples

### From Controller

```php
app(ApplyPloPenaltyAction::class)->handle($params);
```

### From Job/Command

```php
(new ApplyPloPenaltyAction($dependency))->handle($params);
```

## Related Components

- **Controller**: `{RelatedController}` (if applicable)
- **Route**: `{RouteName}` ({HttpMethod} {RoutePath}) (if applicable)
- **Model**: `{RelatedModel}` (if applicable)

## Notes

- When the PLO penalty no longer applies, the existing `applied_penalties` row is deleted **and** the parent `rake_charges` PENALTY aggregate is recalculated, so the aggregate does not keep the removed amount.
