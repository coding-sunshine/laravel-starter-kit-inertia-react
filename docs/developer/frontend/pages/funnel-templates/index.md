# Funnel Templates Page

Lists and manages automated lead nurture funnel templates.

## Location

`resources/js/pages/funnel-templates/index.tsx`

## Route

`GET /funnel/templates` → `funnel.templates.index`

## Props

| Prop | Type | Description |
|------|------|-------------|
| `templates` | paginated | FunnelTemplate records with instances count |
| `stats` | object | total_templates, active_instances, completed_instances |

## Features

- Stats cards (total templates, active enrollments, completed)
- Grid of template cards with type badges
- Active/inactive status indicator
- Enrollment count per template

## Pan Analytics

`data-pan="funnel-templates-tab"` on root container.

## Related

- `app/Http/Controllers/FunnelTemplateController.php`
- `app/Models/FunnelTemplate.php`
- `app/Models/FunnelInstance.php`
