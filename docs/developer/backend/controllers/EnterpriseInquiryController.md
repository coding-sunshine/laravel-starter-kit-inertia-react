# EnterpriseInquiryController

## Purpose

Shows the enterprise inquiry form and stores submissions (with Honeypot).

## Location

`app/Http/Controllers/EnterpriseInquiryController.php`

## Methods

| Method | HTTP | Route | Purpose |
|--------|------|-------|---------|
| `create` | GET | `enterprise-inquiries.create` | Show enterprise inquiry form |
| `store` | POST | `enterprise-inquiries.store` | Store submission, redirect back with status |

## Routes

- `enterprise-inquiries.create`: GET `enterprise` — Enterprise inquiry form
- `enterprise-inquiries.store`: POST `enterprise` — Submit inquiry (ProtectAgainstSpam)

## Actions Used

- `StoreEnterpriseInquiryAction` — Persist inquiry

## Validation

- `StoreEnterpriseInquiryRequest` — name, email, company, phone, message

## Related Components

- **Page**: `enterprise-inquiries/create`
- **Actions**: `StoreEnterpriseInquiryAction`
- **Routes**: `enterprise-inquiries.create`, `enterprise-inquiries.store`
- **Filament**: `EnterpriseInquiryResource` (Billing group)
