# BrochureLayoutController

Manages brochure layout configurations and triggers AI-enhanced PDF generation (v2).

## Location

`app/Http/Controllers/BrochureLayoutController.php`

## Routes

| Method | URI | Name | Description |
|--------|-----|------|-------------|
| GET | `/brochure-layouts` | `brochure-layouts.index` | List layouts and flyers |
| POST | `/brochure-layouts` | `brochure-layouts.store` | Create new layout |
| POST | `/brochure-layouts/flyers/{flyer}/generate-pdf` | `brochure-layouts.generate-pdf` | Generate brochure PDF |

## Actions

- `GenerateBrochureV2Action` — AI brochure generation

## Page

`resources/js/pages/brochure-layouts/index.tsx`
