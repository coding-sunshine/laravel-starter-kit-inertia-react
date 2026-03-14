# ProcessAutomationRuleAction

## Purpose

Executes each action defined in an `AutomationRule`. Increments `run_count` and updates `last_run_at` on completion.

## Location

`app/Actions/ProcessAutomationRuleAction.php`

## Method Signature

```php
public function handle(AutomationRule $rule, array $payload): void
```

## Supported Action Types

| Type | Description |
|------|-------------|
| `send_notification` | Sends `GenericDatabaseNotification` to specified user |
| `create_task` | Stub — logs intent for future implementation |
| `update_field` | Stub — logs intent for future implementation |
| `send_email` | Stub — logs intent for future implementation |

## Related Components

- **Action**: `EvaluateAutomationRulesAction`
- **Model**: `AutomationRule`
