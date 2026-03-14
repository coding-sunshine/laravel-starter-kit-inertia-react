# Custom Fields Page

## Location

`resources/js/pages/custom-fields/index.tsx`

## Route

`GET /custom-fields` — name: `custom-fields.index`

## Purpose

Lists custom fields grouped by entity type (contact, sale, lot, project). Provides an inline form to add new fields per entity type and a delete button per field.

## Props

| Prop | Type | Description |
|------|------|-------------|
| `customFields` | `Record<string, CustomField[]>` | Fields grouped by entity_type |

## Features

- Fields grouped in accordion-style sections per entity type
- Inline add form with: name, key (auto-slugified), type selector, required checkbox
- Delete with confirmation
- Pan analytics: `custom-fields-tab`
