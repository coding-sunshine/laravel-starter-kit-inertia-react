# SignupController

## Purpose

Handles the self-service signup flow: plan selection, registration, org provisioning, and guided onboarding checklist.

## Location

`app/Http/Controllers/SignupController.php`

## Routes

| Method | URL | Name | Description |
|--------|-----|------|-------------|
| GET | /signup | signup.index | Plan selection page |
| GET | /signup/register | signup.register | Registration form |
| POST | /signup/provision | signup.provision | Create user, org, subscription |
| GET | /signup/complete | signup.complete | Redirect to onboarding |
| GET | /signup/onboarding | signup.onboarding | Guided onboarding checklist |
| POST | /signup/onboarding/{stepKey}/complete | signup.onboarding.complete-step | Mark a step done |

## Key Behaviour

- `provision()`: Creates user → assigns subscriber role → auto-creates Organization (owner_id = user) → adds to org_user pivot → subscribes to plan → provisions features → initialises onboarding checklist → initiates billing checkout
- Subscriber never sees a "Create Organisation" form — org is provisioned automatically
- Billing drivers (Stripe/eWAY) are configurable via `BILLING_GATEWAY` env — stubs return success when credentials are not set
- Fires `SubscriberSignedUpEvent` after provisioning

## Dependencies

- `ProvisionSubscriberFeaturesAction`
- `SubscriptionBillingContract` (bound in AppServiceProvider)
