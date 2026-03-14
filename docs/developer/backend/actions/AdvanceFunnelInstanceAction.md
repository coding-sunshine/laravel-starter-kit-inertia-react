# AdvanceFunnelInstanceAction

Advances a funnel instance to the next step and marks it completed when all steps are done.

## Location

`app/Actions/AdvanceFunnelInstanceAction.php`

## Usage

```php
use App\Actions\AdvanceFunnelInstanceAction;
use App\Models\FunnelInstance;

$action = app(AdvanceFunnelInstanceAction::class);
$instance = $action->handle($instance);
// Returns updated FunnelInstance
```

## Parameters

| Parameter | Type | Description |
|-----------|------|-------------|
| `$instance` | `FunnelInstance` | The funnel instance to advance |

## Behaviour

- Increments `current_step` by 1.
- Loads `template` relationship if not loaded.
- If `current_step >= count(config.email_sequences)`, sets `status = 'completed'` and `completed_at = now()`.
- Saves and returns the updated instance.

## Related

- `app/Models/FunnelInstance.php`
- `app/Models/FunnelTemplate.php`
- `app/Http/Controllers/FunnelTemplateController.php`
