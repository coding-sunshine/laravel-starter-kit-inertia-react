# PaymentStageController

**Location**: `app/Http/Controllers/PaymentStageController.php`
**Last Updated**: 2026-03-15

## Routes

| Method | URI | Name | Description |
|--------|-----|------|-------------|
| GET | `sales/{sale}/payment-stages` | `payment-stages.index` | List all stages for a sale |
| POST | `sales/{sale}/payment-stages` | `payment-stages.store` | Create a new stage |
| PATCH | `payment-stages/{paymentStage}` | `payment-stages.update` | Update an existing stage |
| DELETE | `payment-stages/{paymentStage}` | `payment-stages.destroy` | Delete a stage |

## Actions Used

- `CreatePaymentStageAction`

## Stage Values

`eoi`, `deposit`, `commission`, `payout`

## Middleware

Requires `auth` + `verified`.
