# Payment Gateway Decision

**Status:** Decided. Dual-gateway signup (Stripe + eWAY) via `BillingGatewayContract` driver pattern. eWAY also handles reservation/deposit flows.

## Context

- The starter kit integrates **Stripe** (and Paddle) for subscription billing via Laravel Cashier and `laravelcm/laravel-subscriptions`.
- The legacy Fusion app uses **eWAY** for card flows. Existing subscribers are accustomed to paying via eWAY.
- Step 23 (signup/onboarding) and Step 4 (reservations/sales) both touch payments; Step 22 (Xero) reconciles invoices.
- Both Stripe and eWAY must be supported for subscription checkout so admins and subscribers can choose.

## Decision

| Use case | Gateway / stack | Notes |
|----------|-----------------|-------|
| **Subscription plans — Stripe path** | **Stripe** via Laravel Cashier + kit billing | Default gateway; kit tables (`plans`, `plan_subscriptions`) used directly. |
| **Subscription plans — eWAY path** | **eWAY** via Saloon + `EwayBillingDriver` | Tokenised recurring via eWAY Rapid API; subscription state still stored in kit `plan_subscriptions` table (driver creates/updates the row after successful charge). |
| **Reservation / deposit fees** (property flows, one-off charges) | **eWAY** via Saloon `EwayPaymentConnector` | Charge APIs for reservations stay on eWAY; no subscription logic here. |
| **Xero reconciliation** (Step 22) | Works against invoices/payments regardless of gateway; map eWAY transaction refs and Stripe invoice IDs into reconciliation rows. | |

## BillingGatewayContract Driver Pattern (Step 23)

```php
// app/Billing/Contracts/BillingGatewayContract.php
interface BillingGatewayContract {
    public function checkout(User $user, Plan $plan, array $paymentData): CheckoutResult;
    public function cancel(User $user): void;
    public function resume(User $user): void;
}

// app/Billing/Drivers/StripeBillingDriver.php  — uses Laravel Cashier
// app/Billing/Drivers/EwayBillingDriver.php    — uses Saloon EwayConnector (tokenised recurring)

// config/billing.php
'driver' => env('BILLING_GATEWAY', 'stripe'), // 'stripe' | 'eway'
```

- `SignupController` resolves `BillingGatewayContract` from the container.
- The signup form shows a gateway selector (or admin pre-configures which gateway is active via `BILLING_GATEWAY` env / Feature flag).
- After successful charge via either driver, `plan_subscriptions` row is created/updated so kit billing state is consistent regardless of gateway.
- `EwayBillingDriver` stores `eway_token_customer_id` on the user or subscription row for future recurring charges.

## Env Variables

```dotenv
# Stripe (default)
STRIPE_KEY=
STRIPE_SECRET=
STRIPE_WEBHOOK_SECRET=

# eWAY
EWAY_API_KEY=
EWAY_API_PASSWORD=
EWAY_ENDPOINT=https://api.ewaypayments.com   # or sandbox

# Gateway selector (step 23 signup + subscriptions)
BILLING_GATEWAY=stripe   # or: eway
```

## Implementation Notes

- **Step 23:** Seed price points into kit **plans** (price, billing_interval, features JSON). Checkout routes through `BillingGatewayContract` — either Stripe Cashier or eWAY tokenised recurring. Do not introduce a second subscription store; both drivers write to kit tables.
- **Step 4 / reservation flows:** `EwayPaymentConnector` (Saloon) — separate from `EwayBillingDriver`; used only for one-off reservation/deposit charges. Store gateway ref on reservation/sale rows for audit and Xero.
- **Webhook handling:** Stripe webhooks handled via kit's existing Stripe webhook controller. eWAY uses response-based confirmation (no webhook needed for tokenised flow); optionally implement eWAY IPN endpoint for async notification.

## Related

- **00-database-design.md** — no custom billing tables; use kit billing + eWAY refs on domain tables where needed.
- **23-step-17-phase-2-signup-onboarding.md** — signup payment via `BillingGatewayContract` (Stripe or eWAY).
- **04-step-3-projects-and-lots.md** / **05-step-4-reservations-sales-commissions.md** — eWAY one-off charges for reservations.
