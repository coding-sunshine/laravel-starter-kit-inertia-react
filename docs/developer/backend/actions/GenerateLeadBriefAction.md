# GenerateLeadBriefAction

## Purpose

Generates a detailed lead brief/contact profile from available CRM data using Prism AI, with fallback to rule-based summary.

## Location

`app/Actions/GenerateLeadBriefAction.php`

## Method Signature

```php
public function handle(Contact $contact): array
```

## Dependencies

- `PrismService` — AI text generation

## Return Value

Returns an array with keys: brief (string), generated_at (ISO 8601), model (string).

## Related Components

- **Controller**: `LeadGenerationController`
- **Route**: `lead-generation.lead-brief` (POST /lead-generation/lead-brief/{contact})
