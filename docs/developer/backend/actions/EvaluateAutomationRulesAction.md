# EvaluateAutomationRulesAction

## Purpose

Loads active `AutomationRule` records matching the given event for the current tenant, evaluates their conditions against the payload, and dispatches `ProcessAutomationRuleAction` for matching rules.

## Location

`app/Actions/EvaluateAutomationRulesAction.php`

## Method Signature

```php
public function handle(string $event, array $payload): void
```

## Dependencies

- `ProcessAutomationRuleAction` — executes each matched rule
- `TenantContext` — reads current organization ID

## Supported Events

- `contact.stage_changed` — triggered by `UpdateContactStageAction`
- `sale.status_changed` — triggered by `UpdateSaleStageAction`

## Related Components

- **Action**: `ProcessAutomationRuleAction`
- **Model**: `AutomationRule`
- **Integration**: `UpdateContactStageAction`, `UpdateSaleStageAction`
