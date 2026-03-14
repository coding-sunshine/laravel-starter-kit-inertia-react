# GenerateColdOutreachAction

## Purpose

Generates AI-powered cold outreach copy (email/SMS) using Prism, with fallback to rule-based templates.

## Location

`app/Actions/GenerateColdOutreachAction.php`

## Method Signature

```php
public function handle(string $channel, string $tone, array $context, int $organizationId): ColdOutreachTemplate
```

## Dependencies

- `PrismService` — AI text generation

## Return Value

Returns a saved `ColdOutreachTemplate` model with AI-generated subject, body, and CTAs.

## Related Components

- **Controller**: `ColdOutreachController`
- **Route**: `cold-outreach.generate` (POST /cold-outreach/generate)
