# RouteLeadAction

## Purpose

Auto-assigns a contact to an agent using round-robin routing (fewest active contacts) after computing the lead score.

## Location

`app/Actions/RouteLeadAction.php`

## Method Signature

```php
public function handle(Contact $contact): ?User
```

## Dependencies

- `LeadScoringService` — computes lead score before routing

## Return Value

Returns the assigned `User` (agent) or null if no agents available.

## Related Components

- **Controller**: `LeadGenerationController`
- **Route**: `lead-generation.score-and-route` (POST /lead-generation/score-and-route/{contact})
