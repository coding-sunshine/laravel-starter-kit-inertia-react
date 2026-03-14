# AdTemplateController

Manages ad and social media templates with AI copy generation.

## Location

`app/Http/Controllers/AdTemplateController.php`

## Routes

| Method | URI | Name | Description |
|--------|-----|------|-------------|
| GET | `/ad-templates` | `ad-templates.index` | List all templates |
| POST | `/ad-templates` | `ad-templates.store` | Create template with optional AI copy |
| POST | `/ad-templates/generate-copy` | `ad-templates.generate-copy` | Generate AI copy (JSON) |
| DELETE | `/ad-templates/{adTemplate}` | `ad-templates.destroy` | Delete template |

## Actions

- `GenerateAdCopyAction` — AI copy generation

## Page

`resources/js/pages/ad-templates/index.tsx`
