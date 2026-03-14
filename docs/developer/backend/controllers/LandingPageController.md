# LandingPageController

Manages AI-generated landing page templates for real estate campaigns.

## Location

`app/Http/Controllers/LandingPageController.php`

## Routes

| Method | URI | Name | Description |
|--------|-----|------|-------------|
| GET | `/landing-pages` | `landing-pages.index` | List pages |
| POST | `/landing-pages/generate` | `landing-pages.generate` | AI generate new page |
| PATCH | `/landing-pages/{landingPageTemplate}` | `landing-pages.update` | Update page |
| DELETE | `/landing-pages/{landingPageTemplate}` | `landing-pages.destroy` | Delete page |

## Actions

- `GenerateLandingPageAction` — AI content generation

## Page

`resources/js/pages/landing-pages/index.tsx`
