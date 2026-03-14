# GenerateLandingPageAction

Generates an AI-powered landing page template for a real estate project and persists it to the database.

## Location

`app/Actions/GenerateLandingPageAction.php`

## Usage

```php
use App\Actions\GenerateLandingPageAction;

$action = app(GenerateLandingPageAction::class);
$page = $action->handle('Harbour Views Estate', 'Luxury waterfront apartments', 'investors');
// Returns LandingPageTemplate
```

## Parameters

| Parameter | Type | Description |
|-----------|------|-------------|
| `$projectName` | `string` | Name of the real estate project |
| `$description` | `string` | Project description |
| `$targetAudience` | `string` | Target audience (default: 'home buyers') |

## Behaviour

- Calls Prism to generate headline, sub-headline, HTML, and SEO metadata.
- Creates a `LandingPageTemplate` with status `draft`.
- Generates a unique slug via `Str::slug` + `Str::random(6)`.
- Falls back to default HTML template if AI unavailable.
- Deducts credits with action key `generate_landing_page`.

## Related

- `app/Models/LandingPageTemplate.php`
- `app/Http/Controllers/LandingPageController.php`
