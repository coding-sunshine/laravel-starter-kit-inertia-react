# FunnelTemplateController

Manages funnel templates and enrolls contacts into automated funnels.

## Location

`app/Http/Controllers/FunnelTemplateController.php`

## Routes

| Method | URI | Name | Description |
|--------|-----|------|-------------|
| GET | `/funnel/templates` | `funnel.templates.index` | List templates |
| POST | `/funnel/templates` | `funnel.templates.store` | Create template |
| POST | `/funnel/templates/{template}/enroll/{contact}` | `funnel.templates.enroll` | Enroll contact |

## Related

- `app/Models/FunnelTemplate.php`
- `app/Models/FunnelInstance.php`
- `resources/js/pages/funnel-templates/index.tsx`
