# Step 23 (Step 17): Phase 2 — Auto Signup & Guided Onboarding

## Goal

Enable **self-service signup** (public form), **plan selection** (Monthly $330+setup, Monthly $415, Annual $3,960), **payment for subscriptions via Stripe or eWAY** (via `BillingGatewayContract` driver — kit billing tables used regardless of gateway), **provisioning** with permissions and feature flags, and **guided onboarding** (checklist: set password, sign agreement, CRM tour, upload contacts, connect website, launch flyer, meet BDM). Builds on Steps 0–22. This step also covers **SaaS (v4) product leads**: capture demo/trial requests as Contact (contact_origin = saas_product) and convert them to signup.

**Payment split (see [00-payment-gateway-decision.md](./00-payment-gateway-decision.md)):**
- **Subscriptions / signup checkout:** **Stripe** (default, Laravel Cashier) **or eWAY** (tokenised recurring, Saloon) — configured via `BILLING_GATEWAY` env or admin setting. Both drivers write subscription state to kit `plan_subscriptions` table.
- **Reservation/deposit fees (property flows):** **eWAY** via Saloon `EwayPaymentConnector` — separate from the subscription billing driver; implemented in Step 4.

## Starter Kit References

- **Auth**: Registration flow; kit Fortify/registration
- **Billing**: Kit billing tables (`plans`, `plan_subscriptions`, seat billing) — both drivers write here; do not duplicate with custom `subscriptions` tables
- **Saloon**: `EwayBillingDriver` for eWAY tokenised recurring; `StripeBillingDriver` wraps Laravel Cashier
- **Filament**: Onboarding progress or dashboard widget

## Deliverables

1. **Routes**: GET/POST /signup; API POST /api/auth/register (if used).
2. **BillingGatewayContract drivers**: Implement `app/Billing/Contracts/BillingGatewayContract.php` interface with `checkout()`, `cancel()`, `resume()`. Implement `StripeBillingDriver` (Cashier) and `EwayBillingDriver` (Saloon — tokenised recurring via eWAY Rapid API, stores `eway_token_customer_id` on subscription or user). Bind in `AppServiceProvider` based on `config(‘billing.driver’)` (env `BILLING_GATEWAY`).
3. **Signup form**: Full name, email, mobile, business name, ABN, referral code (optional), plan selection, payment (gateway-aware — shows Stripe card element OR eWAY hosted fields based on active driver; admin can pre-configure or allow user to select gateway at checkout).
4. **Plan logic** (fully flexible — superadmin editable at runtime):

   Plans are stored in kit `plans` table (laravelcm/laravel-subscriptions). Superadmin manages all plan details via **Filament → Plans** (create, edit, delete, reorder). The seed data below is the default starting state only — every field is editable.

   **Extended plan fields** (add via migration on `plans` table):
   - `ai_credits_per_period INT DEFAULT 100` — AI credits allocated per billing period (see `00-ai-credits-system.md`)
   - `setup_fee DECIMAL(10,2) DEFAULT 0` — one-time setup fee at signup
   - `is_public BOOL DEFAULT TRUE` — show on public pricing page
   - `max_users INT DEFAULT NULL` — max team members (null = unlimited)
   - `max_php_sites INT DEFAULT 0` / `max_wp_sites INT DEFAULT 0` — site limits per plan

   **Flexible plan config stored in `plans.features` JSON:**
   ```json
   {
     "flags": ["PropertyAccessFeature", "BotInABoxFeature", "SprFeature", "CampaignWebsitesFeature"],
     "ai_credits": 100,
     "max_users": null,
     "max_php_sites": 1,
     "max_wp_sites": 0
   }
   ```

   **Feature Flag → Plan Tier Mapping** (seed defaults — all editable by superadmin):

   | Feature Class | Starter $330/mo + setup | Growth $415/mo | Enterprise (custom) |
   |---|---|---|---|
   | `PropertyAccessFeature` | ✅ | ✅ | ✅ |
   | `AiToolsFeature` | ❌ | ✅ | ✅ |
   | `AiBotsCustomFeature` | ❌ | ✅ | ✅ |
   | `BotInABoxFeature` | ✅ (system bots only) | ✅ (custom + system) | ✅ |
   | `SprFeature` | ✅ | ✅ | ✅ |
   | `ApiAccessFeature` | ❌ | ✅ | ✅ |
   | `WebsitesFeature` | ✅ (1 PHP site) | ✅ (2 PHP + 3 WP) | ✅ |
   | `WordPressSitesFeature` | ❌ | ✅ | ✅ |
   | `PhpSitesFeature` | ✅ (1 site) | ✅ (2 sites) | ✅ |
   | `CampaignWebsitesFeature` | ✅ | ✅ | ✅ |
   | `FlyersFeature` | ✅ | ✅ | ✅ |
   | `XeroIntegrationFeature` | ❌ | ✅ | ✅ |
   | `ImpersonationFeature` | ❌ | ❌ | ✅ (admin only) |
   | `AdvancedReportsFeature` | ❌ | ✅ | ✅ |

   Seed plans with `laravelcm/laravel-subscriptions` seeder (or `DatabaseSeeder`). Each plan row: `name`, `slug`, `price`, `billing_interval`, `features` (JSON array of enabled Feature class names or slugs). During provisioning in `SignupController`, after creating the subscription via `BillingGatewayContract::checkout()`, call `Pennant::for($user)->activate(PropertyAccessFeature::class)` (and other features per plan) — or use a `ProvisionSubscriberFeatures` action class that reads plan features and activates them in bulk.

   **Annual plan ($3,960)**: Same features as Growth $415/mo tier; different billing_interval and price (12 × $330 = $3,960).

5. **Provisioning**: Create user (role: subscriber); **system auto-creates one organization** with `owner_id` = the new user (name = business name from signup form, fallback "Org of {name}") and adds the user to `organization_user` with `is_default = true` and `role = 'owner'`; set feature flags via `ProvisionSubscriberFeatures`; log source and referral; redirect to dashboard with onboarding.

   **Org creation rules (mirrors Step 2 import logic):**
   - The subscriber never sees a "Create Organisation" button — the system creates it inside `SignupController::provision()`.
   - Use `Organization::firstOrCreate(['owner_id' => $user->id], [...])` so re-running provisioning (e.g. retry after failed payment) is idempotent.
   - `OrganizationPolicy::create()` is superadmin-only (set in Step 0) — even if a subscriber somehow hits the create endpoint, it is rejected by the policy gate.
   - Org slug is generated from the business name; ensure uniqueness with a suffix counter if collision.
   - After org creation, all subsequent CRM data created by this subscriber (contacts, projects, tasks, etc.) uses `organization_id = $org->id` as the default scope.

5b. **AI Credits provisioning**: On signup, create an `ai_credit_pools` row for the new org: `credits_total = plan.ai_credits_per_period`, `period_start = today`, `period_end = today + 1 month`. See **`00-ai-credits-system.md`** for full schema and service class. Superadmin can top-up or adjust via Filament → Settings → AI Credits.

6. **Onboarding checklist**: `onboarding_progress` table tracks step completion per user.

   **Step keys (exact enum values for `onboarding_progress.step_key`):**
   ```php
   enum OnboardingStep: string {
       case SetPassword      = 'set_password';       // change temp password
       case SignAgreement    = 'sign_agreement';      // sign subscriber agreement PDF
       case CrmTour          = 'crm_tour';            // complete guided tour (interactjs or video)
       case UploadContacts   = 'upload_contacts';     // import first contacts CSV
       case ConnectWebsite   = 'connect_website';     // create first PHP/WP/campaign site
       case LaunchFlyer      = 'launch_flyer';        // create and download first flyer
       case MeetBdm          = 'meet_bdm';            // schedule BDM call or complete AI intro
   }
   ```

   Schema: `onboarding_progress (id, user_id UNIQUE+step_key UNIQUE, step_key, completed_at, created_at)`. Show progress widget on dashboard (step list with tick marks). All steps optional to complete — just tracked. Mark complete automatically where possible (e.g. `UploadContacts` marks when first import runs; `LaunchFlyer` marks when first flyer downloaded).

7. **Email triggers (laravel-database-mail)**: Create **SubscriberSignedUpEvent** (welcome), **OnboardingReminderEvent** (weekly reminders if incomplete); register in `config/database-mail.php`. Do not use raw Mail::send(); use the kit’s event-driven templates. See 00-kit-package-alignment.md.

8. **Referral**: Use **jijunair/laravel-referral** for referral code generation, tracking, and reward attribution on signup (signup form has optional referral_code).

9. **Vouchers (optional):** Kit has **beyondcode/laravel-vouchers: ^2.3** — use for setup-fee discounts or promo codes during signup if desired; complements referral. See 00-kit-package-alignment.md.

10. **Signup form spam**: Add **spatie/laravel-honeypot** (@honeypot in form) and ProtectAgainstSpam middleware to the public signup form.

11. **Admin visibility**: Signup report; signup-to-sale conversion; "New Subscribers This Month" widget; users.created_via = ‘auto’.

12. **SaaS (v4) product leads**: Public forms (e.g. "Request demo", "Start trial") create a **Contact** with contact_origin = `saas_product` (and type = lead, organization_id = dedicated SaaS org or null). Internal/superadmin manage these in a separate Contact list (filter by contact_origin). When a SaaS lead converts: create **user** (role subscriber), link user.contact_id to that contact (or create org contact for the new tenant), mark contact stage as converted; optionally move to property org when they sign up. So SaaS leads are **software (product) leads for v4**; property leads (contact_origin = property) remain the CRM’s main lead type for admin and subscribers.

## DB Design (this step)

- **onboarding_progress** (user_id, step_key, completed_at). **Plans/subscriptions:** Use kit tables only (plans, plan_subscriptions, etc.); extend with features JSON as needed. Extend users with `created_via`, `referral_code`. Add `eway_token_customer_id` (nullable string) to `users` or `plan_subscriptions` for eWAY tokenised recurring. Both drivers (Stripe + eWAY) write to `plan_subscriptions`; gateway used is identifiable via `gateway` column (add: `stripe` | `eway`). eWAY one-off reservation refs live on reservation/sale rows from Step 4.

## Data Import

- **None.**

## AI Enhancements

- Optional: AI-driven onboarding using Bot In A Box.

## Verification (verifiable results)

- Complete signup with plan and payment; confirm user and flags; complete onboarding steps; confirm emails and admin report.

## Human-in-the-loop (end of step)

**STOP after this step. Do not proceed to Step 24 until the human has completed the checklist below.**

Human must:
- [ ] Confirm signup form and plan selection work.
- [ ] Confirm payment completes via **Stripe** (default) AND test **eWAY** tokenised path; confirm user is provisioned and `plan_subscriptions.gateway` column records which gateway was used.
- [ ] Confirm each new signup gets an organization with owner_id = new user (subscriber is org owner; can manage team under that org).
- [ ] Confirm onboarding checklist tracks and displays progress.
- [ ] Confirm welcome and reminder emails send.
- [ ] Confirm admin sees signup report and new subscribers widget.
- [ ] Approve proceeding to Step 24 (Phase 2: R&D & special).

## Acceptance Criteria

- [ ] Self-service signup, plan selection, payment, provisioning, onboarding checklist, email triggers, and admin visibility delivered per scope.
