# PRD 18: Signup & Onboarding

> **Phase 2** — Note: Builder self-signup ships in Phase 1 (PRD 12). This PRD covers subscriber onboarding which runs in Phase 2.

## Overview

Enable self-service public signup, plan selection with Stripe payment, automated org provisioning with feature flags, guided onboarding wizard, referral system, SaaS product lead capture, and admin signup visibility. Stripe handles all payments: subscriptions (Stripe Billing), one-time credit purchases (Stripe Checkout), and reservation deposits (Stripe Payment Intents).

**Prerequisites:** PRDs 00-17 complete (Xero integration, API, all CRM features working).

## Technical Context

- **Billing:** `laravelcm/laravel-subscriptions` (Plan, PlanSubscription in kit `plans`/`plan_subscriptions` tables)
- **Stripe:** `stripe/stripe-php` — Stripe handles all payments (subscriptions, one-time credits, deposits). Use directly, no gateway abstraction needed.
- **Onboarding:** `spatie/laravel-onboard` for checklist tracking
- **Referral:** `jijunair/laravel-referral` for referral codes and tracking
- **Honeypot:** `spatie/laravel-honeypot` for form spam protection
- **Vouchers:** `beyondcode/laravel-vouchers` (v2.3) for promo codes (optional)
- **Email:** Starter kit's `EmailTemplatesController` + org-scoped `mail_templates` for event-triggered welcome/reminder emails. CRUD at `/settings/email-templates`.
- **Env:** `STRIPE_KEY`, `STRIPE_SECRET`, `STRIPE_WEBHOOK_SECRET`

### Required Environment Variables
Before starting this PRD, verify these are set in `.env`:
- `STRIPE_KEY` — Stripe publishable key (required for payment form)
- `STRIPE_SECRET` — Stripe secret key (required for server-side charges)
- `STRIPE_WEBHOOK_SECRET` — Stripe webhook signing secret (required for payment event handling)

**If any required key is missing, ASK the user before proceeding. Do not skip, stub, or use placeholder values.**

## User Stories

### US-001: Stripe Payment Integration

**Status:** todo
**Priority:** 1
**Description:** Implement Stripe payment integration for subscriptions, one-time credit purchases, and reservation deposits.

- [ ] Configure `stripe/stripe-php` with `STRIPE_KEY` and `STRIPE_SECRET` from `.env`
- [ ] Create `app/Billing/StripeCheckoutService.php`: handles subscription creation (Stripe Billing), one-time credit purchases (Stripe Checkout Sessions), and reservation deposits (Stripe Payment Intents)
- [ ] Write subscription state to kit `plan_subscriptions` table after successful Stripe charge
- [ ] Configure Stripe webhook handler at `/api/webhooks/stripe` for payment events (invoice.paid, checkout.session.completed, payment_intent.succeeded)
- [ ] Verify: Stripe checkout creates subscription record; webhook updates payment status

### US-002: Plan Configuration & Seeding

**Status:** todo
**Priority:** 1
**Description:** Seed subscription plans with extended fields and feature flags.

- [ ] Add migration to `plans` table: `ai_credits_per_period` (int default 100), `setup_fee` (decimal 10,2 default 0), `is_public` (boolean default true), `max_users` (int nullable), `max_php_sites` (int default 0), `max_wp_sites` (int default 0)
- [ ] Plans store feature flags in `features` JSON column: array of enabled Feature class names
- [ ] Seed 3 plans: Starter ($330/mo + $330 setup), Growth ($415/mo), Annual ($3,960/yr — same features as Growth)
- [ ] Feature flag mapping per plan as specified in spec (PropertyAccessFeature, AiToolsFeature, etc.)
- [ ] Superadmin can CRUD plans via Filament (all fields editable at runtime)
- [ ] Verify: 3 plans seeded with correct prices and feature flags

### US-003: Public Signup Form

**Status:** todo
**Priority:** 1
**Description:** Build the public signup form with plan selection and payment.

- [ ] Create routes: `GET /signup`, `POST /signup`
- [ ] Signup form fields: full name, email, mobile, business name, ABN (optional), referral code (optional), plan selection (radio/cards showing plan features + price)
- [ ] Payment section: Stripe card element (via Stripe.js + Elements)
- [ ] Apply `spatie/laravel-honeypot` @honeypot directive + `ProtectAgainstSpam` middleware
- [ ] Server-side validation: required fields, email unique, mobile format
- [ ] On submit: create user, process payment via Stripe, provision org
- [ ] Verify: completing signup form creates user + subscription + org; honeypot blocks bot submissions

### US-004: Subscriber Provisioning

**Status:** todo
**Priority:** 1
**Description:** Auto-provision organization, feature flags, and AI credits on successful signup.

- [ ] Create `ProvisionSubscriberAction`: called after successful payment
  - Create Organization: `Organization::firstOrCreate(['owner_id' => $user->id], ['name' => $businessName, 'slug' => Str::slug($businessName)])` with uniqueness suffix
  - Add user to `organization_user` with `is_default = true`, `role = 'owner'`
  - Set user role: `subscriber`
  - Activate feature flags: `ProvisionSubscriberFeatures` action reads plan `features` JSON, calls `Pennant::for($user)->activate()` for each
  - Create `ai_credit_pools` row: credits_total from plan, period_start = today, period_end = +1 month
  - Set `users.created_via = 'auto'`
  - Log source and referral attribution
- [ ] `OrganizationPolicy::create()` is superadmin-only — subscribers cannot create additional orgs
- [ ] Idempotent: `firstOrCreate` handles retry scenarios
- [ ] Verify: after signup, user has org with owner role; feature flags active; AI credits allocated

### US-005: Guided Onboarding Wizard

**Status:** todo
**Priority:** 2
**Description:** Track and display onboarding progress for new subscribers.

- [ ] Create migration `create_onboarding_progress_table`: id, user_id (FK users), step_key (string enum: set_password/sign_agreement/crm_tour/upload_contacts/connect_website/launch_flyer/meet_bdm), completed_at (timestamp nullable), created_at. Unique(user_id, step_key)
- [ ] Create `OnboardingStep` enum in `modules/module-crm/src/Enums/`
- [ ] Dashboard widget showing onboarding checklist with progress indicators (tick marks)
- [ ] Auto-complete where possible: `upload_contacts` marks when first import runs, `launch_flyer` marks when first flyer downloaded
- [ ] All steps optional — just tracked, not enforced
- [ ] Verify: new subscriber sees onboarding widget; completing an import marks `upload_contacts` step

### US-006: Email Triggers for Signup

**Status:** todo
**Priority:** 2
**Description:** Register signup email events with the starter kit's existing email template management system.

> **Starter kit provides:** `EmailTemplatesController`, org-scoped `mail_templates` table, Inertia CRUD at `/settings/email-templates`, `MailTemplatesSeeder` for defaults.

- [ ] Create `SubscriberSignedUpEvent` — dispatched on successful signup → sends welcome email
- [ ] Create `OnboardingReminderEvent` — dispatched weekly for users with incomplete onboarding
- [ ] Register both events in `config/email-templates.php` with default template content and variables
- [ ] Superadmin can customize default templates via Filament system panel; org admins customize via `/settings/email-templates`
- [ ] Templates use variables: {{ user.name }}, {{ org.name }}, {{ onboarding_url }}
- [ ] Verify: signup triggers welcome email; incomplete onboarding after 7 days triggers reminder

### US-007: Referral System

**Status:** todo
**Priority:** 3
**Description:** Implement referral code generation, tracking, and attribution.

- [ ] Configure `jijunair/laravel-referral` on User model
- [ ] Each subscriber gets a unique referral code on provisioning
- [ ] Signup form: optional referral_code field; on signup, attribute new user to referrer
- [ ] Track referral count and conversions per referrer
- [ ] Admin report: referral leaderboard (referrer, referral count, converted count)
- [ ] Verify: signing up with referral code links new user to referrer; referral count increments

### US-008: SaaS Product Leads

**Status:** todo
**Priority:** 3
**Description:** Capture demo/trial requests as contacts and convert to subscribers.

- [ ] Public forms: `/request-demo`, `/start-trial` — create Contact with `contact_origin = 'saas_product'`, `type = 'lead'`, `organization_id = PIAB org`
- [ ] Superadmin manages SaaS leads in Contact list filtered by `contact_origin = 'saas_product'`
- [ ] Conversion action: "Convert to Subscriber" button on SaaS lead → pre-fills signup form with lead data
- [ ] On conversion: create user, link `user.contact_id` to existing contact, mark contact stage as converted
- [ ] Verify: submitting demo request creates saas_product contact; converting creates subscriber with linked contact

### US-009: Admin Signup Visibility

**Status:** todo
**Priority:** 3
**Description:** Provide admin reporting on signups and conversions.

- [ ] Filament dashboard widget: "New Subscribers This Month" count
- [ ] Signup report page: table of recent signups with name, plan, date, referral source, payment status
- [ ] Signup-to-sale conversion metric: % of subscribers who have made at least 1 sale
- [ ] Filter by: date range, plan, referral source
- [ ] Verify: admin dashboard shows new subscriber count; report page lists recent signups

### US-010: Vouchers for Signup Discounts (Optional)

**Status:** todo
**Priority:** 4
**Description:** Allow promo codes for setup fee discounts during signup.

- [ ] Configure `beyondcode/laravel-vouchers` (v2.3)
- [ ] Superadmin creates vouchers via Filament: code, discount amount or percentage, expiry date, max uses
- [ ] Signup form: optional voucher code field; on apply, reduce setup_fee
- [ ] Track voucher redemptions
- [ ] Verify: entering valid voucher code reduces setup fee on checkout
