# GenerateLandingPageCopyAction

## Purpose

Generates high-converting landing page copy from Project or Lot listing data using Prism AI.

## Location

`app/Actions/GenerateLandingPageCopyAction.php`

## Method Signature

```php
public function handle(Project|Lot $listing): array
```

## Dependencies

- `PrismService` — AI text generation

## Return Value

Returns an array with keys: headline, subheadline, hero_copy, features, cta, seo_description.

## Related Components

- **Controller**: `LeadGenerationController`
- **Route**: `lead-generation.landing-page-copy` (POST /lead-generation/landing-page-copy)
