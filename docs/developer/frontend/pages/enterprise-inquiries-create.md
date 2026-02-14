# enterprise-inquiries/create

## Purpose

Public enterprise inquiry form: name, email, company, phone, message. Submissions are stored and visible in Filament (Billing → Enterprise inquiries).

## Location

`resources/js/pages/enterprise-inquiries/create.tsx`

## Route Information

- **URL**: `enterprise`
- **Route Name**: `enterprise-inquiries.create` (GET), `enterprise-inquiries.store` (POST)
- **HTTP Method**: GET (form), POST (submit)
- **Middleware**: `web` (no auth required)

## Props (from Controller)

| Prop | Type | Description |
|------|------|--------------|
| `flash.status` | `string` | Success message after submit |

## User Flow

1. User visits `enterprise` (or clicks "Contact us" on pricing page).
2. Fills name, email, company, phone, message (Honeypot fields are hidden).
3. Submits; redirects back with status. Submissions appear in Filament under Billing → Enterprise inquiries.

## Related Components

- **Controller**: `EnterpriseInquiryController@create`, `EnterpriseInquiryController@store`
- **Action**: `StoreEnterpriseInquiryAction`
- **Routes**: `enterprise-inquiries.create`, `enterprise-inquiries.store`
- **Layout**: `AuthLayout`
- **Component**: `HoneypotFields`
