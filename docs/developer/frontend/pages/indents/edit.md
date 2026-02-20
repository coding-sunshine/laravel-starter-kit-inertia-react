# edit

## Purpose

Allows users to edit an existing indent: update siding, indent number, target/allocated quantity, state, dates, e-Demand reference ID, FNR, remarks, and optionally replace the e-Demand confirmation PDF.

## Location

`resources/js/pages/indents/edit.tsx`

## Route Information

- **URL**: `/indents/{indent}/edit`
- **Route Name**: `indents.edit`
- **HTTP Method**: GET
- **Middleware**: auth, verified

## Props (from Controller)

| Prop   | Type     | Description                          |
|--------|----------|--------------------------------------|
| indent | Indent   | The indent being edited              |
| sidings | Siding[] | Sidings the user can assign to indent |

## User Flow

1. User navigates to an indent show page and clicks "Edit", or goes to `/indents/{id}/edit`.
2. User updates fields (siding, indent number, quantities, state, dates, e-Demand ref, FNR, remarks, optional PDF).
3. User submits; form sends PUT via `router.post()` with `_method: PUT` and `forceFormData: true`.
4. On success, user is redirected to the indent show page.

## Related Components

- **Controller**: `IndentsController@edit`
- **Route**: `indents.edit`
- **Update route**: `indents.update`

## Implementation Details

Uses `formData.append('_method', 'PUT')` and `router.post(\`/indents/${indent.id}\`, formData, { forceFormData: true })` for update with optional file. Validation errors from `usePage().props.errors`.
