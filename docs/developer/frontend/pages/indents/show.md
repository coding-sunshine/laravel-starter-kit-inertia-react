# show

## Purpose

Displays a single indent’s details: indent number, siding, target/allocated quantity, state, dates, optional e-Demand reference ID and FNR number, link to view e-Demand confirmation PDF (when attached), and remarks. Includes an "Edit" link to the indent edit page.

## Location

`resources/js/pages/indents/show.tsx`

## Route Information

- **URL**: `/indents/{indent}`
- **Route Name**: `indents.show`
- **HTTP Method**: GET
- **Middleware**: auth, verified

## Props (from Controller)

| Prop   | Type   | Description        |
|--------|--------|--------------------|
| indent | Indent | The indent record (with siding and appended indent_confirmation_pdf_url) |

## User Flow

1. User navigates from indents list (clicking indent number or "View") or after create/update.
2. Page shows indent details; if an e-Demand confirmation PDF is attached, "View confirmation (PDF)" is shown.
3. User can click "Edit" to go to the edit page.

## Related Components

- **Controller**: `IndentsController@show`
- **Route**: `indents.show`

## Implementation Details

Indent model appends `indent_confirmation_pdf_url` from Spatie Media (collection `indent_confirmation_pdf`). Policy `IndentPolicy@view` enforces siding access.
