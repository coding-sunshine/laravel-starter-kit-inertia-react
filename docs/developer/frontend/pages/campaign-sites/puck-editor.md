# Campaign Site Puck Editor

## Purpose

Full-screen Puck visual page builder for editing campaign site content. Supports CRM data components (ProjectHero, LotGrid, EnquiryForm, TextBlock).

## Location

`resources/js/pages/campaign-sites/puck-editor.tsx`

## Route Information

- **URL**: `/campaign-sites/{campaign}/edit-puck`
- **Route Name**: `campaign-sites.edit-puck`
- **HTTP Method**: GET
- **Middleware**: auth, tenant

## Props (from Controller)

| Prop | Type | Description |
|------|------|-------------|
| campaign | Campaign | Campaign site with id, title, site_id, puck_content, puck_enabled |

## Components

- `ProjectHero` — renders project hero image, title, tagline
- `LotGrid` — grid of available lots for a project
- `EnquiryForm` — contact enquiry form that POSTs to CRM
- `TextBlock` — editable rich text section
