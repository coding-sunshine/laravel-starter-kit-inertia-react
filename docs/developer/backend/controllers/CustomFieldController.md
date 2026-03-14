# CustomFieldController

## Purpose

Manages custom fields per organization. Fields can be attached to contacts, sales, lots, and projects.

## Location

`app/Http/Controllers/CustomFieldController.php`

## Routes

| Method | URI | Route Name | Description |
|--------|-----|------------|-------------|
| GET | `/custom-fields` | `custom-fields.index` | List fields grouped by entity_type |
| POST | `/custom-fields` | `custom-fields.store` | Create a new custom field |
| PATCH | `/custom-fields/{customField}` | `custom-fields.update` | Update a field |
| DELETE | `/custom-fields/{customField}` | `custom-fields.destroy` | Delete a field |
| GET | `/custom-fields/values` | `custom-fields.values` | Get field values for an entity |

## Related Components

- **Model**: `CustomField`, `CustomFieldValue`
- **Page**: `resources/js/pages/custom-fields/index.tsx`
