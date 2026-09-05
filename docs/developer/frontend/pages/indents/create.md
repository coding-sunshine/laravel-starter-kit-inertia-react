# create

## Purpose

Allows users to create a new indent (rake order): select siding, enter indent number, target quantity, dates, optional e-Demand reference ID and FNR, and optionally attach an e-Demand confirmation PDF.

## Location

`resources/js/pages/indents/create.tsx`

## Route Information

- **URL**: `/indents/create`
- **Route Name**: `indents.create`
- **HTTP Method**: GET
- **Middleware**: auth, verified

## Props (from Controller)

| Prop   | Type       | Description                          |
|--------|------------|--------------------------------------|
| sidings | Siding[]  | Sidings the user can assign to indent |

## User Flow

1. User navigates to `/indents` and clicks "Create indent", or goes to `/indents/create`.
2. User selects siding, enters indent number, target quantity (MT), indent date, optional required-by date, e-Demand reference ID, FNR number, and optionally uploads e-Demand confirmation PDF.
3. User submits the form; data is POSTed to `indents.store` with `forceFormData` for file upload.
4. On success, user is redirected to the new indent show page.

## Related Components

- **Controller**: `IndentsController@create`
- **Route**: `indents.create`
- **Store route**: `indents.store`

## Implementation Details

Form uses native form submit with `FormData` and `router.post(..., { forceFormData: true })` for PDF upload. Validation errors come from `usePage().props.errors`.
