# AutomationRuleController

## Purpose

Manages automation rules per organization. Rules define event triggers, conditions, and actions to execute when CRM events occur.

## Location

`app/Http/Controllers/AutomationRuleController.php`

## Routes

| Method | URI | Route Name | Description |
|--------|-----|------------|-------------|
| GET | `/automation-rules` | `automation-rules.index` | List rules |
| POST | `/automation-rules` | `automation-rules.store` | Create a rule |
| PATCH | `/automation-rules/{automationRule}` | `automation-rules.update` | Update a rule |
| DELETE | `/automation-rules/{automationRule}` | `automation-rules.destroy` | Delete (soft) a rule |

## Related Components

- **Model**: `AutomationRule`
- **Action**: `EvaluateAutomationRulesAction`, `ProcessAutomationRuleAction`
- **Page**: `resources/js/pages/automation-rules/index.tsx`
