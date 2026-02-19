# Pages

Inertia pages are React components that receive data from Laravel controllers. They live in `resources/js/pages/`.

## Layout conventions

**Authenticated app pages (dashboard, modules, settings, billing, organizations, etc.) must use the same layout** so the UI is consistent:

- Use **`AppLayout`** from `@/layouts/app-layout` for any page that should show the sidebar, top bar, and breadcrumbs.
- Pass **`breadcrumbs`**: an array of `{ title: string, href: string }` (e.g. Dashboard → Module → optional current page).
- Wrap page content in the same content wrapper used elsewhere:  
  `<div className="flex h-full flex-1 flex-col gap-6 overflow-x-auto rounded-xl p-4">`.

**Do not** use a custom full-page layout (e.g. standalone header + “Back to home”) for app modules. Reserve that for unauthenticated or one-off flows (e.g. welcome, login, legal pages).

Examples: dashboard, blog (index/show), changelog, help (index/show), billing, organizations, settings — all use `AppLayout`.

## Available Pages

| Page | Route | Documented |
|------|-------|------------|
| [appearance/update](./appearance-update.md) | filament.exports.download, filament.imports.failed-rows.download | ✅ |
| [dashboard](./dashboard.md) | dashboard | ✅ |
| [session/create](./session-create.md) | login, login.store | ✅ |
| [user-email-reset-notification/create](./user-email-reset-notification-create.md) | password.request, password.email | ✅ |
| [user-email-verification-notification/create](./user-email-verification-notification-create.md) | verification.notice, verification.send | ✅ |
| [user-password-confirmation/create](./user-password-confirmation-create.md) | N/A | ✅ |
| [user-password/create](./user-password-create.md) | password.edit, password.update | ✅ |
| [user-password/edit](./user-password-edit.md) | password.edit, password.update | ✅ |
| [user-profile/edit](./user-profile-edit.md) | user-profile.edit, user-profile.update | ✅ |
| [user-two-factor-authentication-challenge/show](./user-two-factor-authentication-challenge-show.md) | N/A | ✅ |
| [user-two-factor-authentication/show](./user-two-factor-authentication-show.md) | two-factor.show | ✅ |
| [user/create](./user-create.md) | user.destroy, register | ✅ |
| [welcome](./welcome.md) | filament.exports.download, filament.imports.failed-rows.download | ✅ |
| [contact/create](./contact-create.md) | contact.create, contact.store | ✅ |
| [blog/index](./blog-index.md) | blog.index, blog.show | ✅ |
| [blog/show](./blog-show.md) | blog.index, blog.show | ✅ |
| [changelog/index](./changelog-index.md) | changelog.index | ✅ |
| [help/index](./help-index.md) | help.index, help.show | ✅ |
| [help/show](./help-show.md) | help.index, help.show | ✅ |
| [settings/personal-data-export](./settings-personal-data-export.md) | filament.exports.download, filament.imports.failed-rows.download | ✅ |
| [onboarding/show](./onboarding-show.md) | onboarding, onboarding.store | ✅ |
| [legal/privacy](./legal-privacy.md) | filament.exports.download, filament.imports.failed-rows.download | ✅ |
| [legal/terms](./legal-terms.md) | filament.exports.download, filament.imports.failed-rows.download | ✅ |
| [settings/achievements](docs/developer/frontend/pages/settings/achievements.md) | achievements.show | ✅ |
| [invitations/accept](./invitations/accept.md) | invitations.show, invitations.accept | ✅ |
| [organizations/create](./organizations/create.md) | organizations.index, organizations.create | ✅ |
| [organizations/index](./organizations/index.md) | organizations.index, organizations.create | ✅ |
| [organizations/members](./organizations/members.md) | organizations.members.index, organizations.members.update | ✅ |
| [organizations/show](./organizations/show.md) | organizations.index, organizations.create | ✅ |
| [billing/credits](./billing/credits.md) | billing.credits.index, billing.credits.purchase | ✅ |
| [billing/index](./billing/index.md) | billing.index | ✅ |
| [billing/invoices](./billing/invoices.md) | billing.invoices.index, billing.invoices.download | ✅ |
| [pricing](./pricing.md) | pricing | ✅ |
| [terms/accept](./terms-accept.md) | terms.accept, terms.accept.store | ✅ |
| [enterprise-inquiries/create](./enterprise-inquiries-create.md) | enterprise-inquiries.create, enterprise-inquiries.store | ✅ |
| [alerts/index](./alerts/index.md) | alerts.index, alerts.resolve | ✅ |
| [indents/index](./indents/index.md) | indents.index | ✅ |
| [mobile/SidingDashboard](./mobile/SidingDashboard.md) | N/A | ✅ |
| [penalties/index](./penalties/index.md) | penalties.index | ✅ |
| [railway-receipts/create](./railway-receipts/create.md) | railway-receipts.index, railway-receipts.create | ✅ |
| [railway-receipts/index](./railway-receipts/index.md) | railway-receipts.index, railway-receipts.create | ✅ |
| [railway-receipts/show](./railway-receipts/show.md) | railway-receipts.index, railway-receipts.create | ✅ |
| [rakes/index](./rakes/index.md) | rakes.index, rakes.show | ✅ |
| [rakes/show](./rakes/show.md) | rakes.index, rakes.show | ✅ |
| [reconciliation/index](./reconciliation/index.md) | reconciliation.index, reconciliation.show | ✅ |
| [reconciliation/power-plant-receipts/create](./reconciliation/power-plant-receipts/create.md) | reconciliation.power-plant-receipts.index, reconciliation.power-plant-receipts.create | ✅ |
| [reconciliation/power-plant-receipts/index](./reconciliation/power-plant-receipts/index.md) | reconciliation.power-plant-receipts.index, reconciliation.power-plant-receipts.create | ✅ |
| [reconciliation/show](./reconciliation/show.md) | reconciliation.index, reconciliation.show | ✅ |
| [reports/index](./reports/index.md) | reports.index, reports.generate | ✅ |
| [road-dispatch/arrivals/create](./road-dispatch/arrivals/create.md) | road-dispatch.arrivals.index, road-dispatch.arrivals.create | ✅ |
| [road-dispatch/arrivals/index](./road-dispatch/arrivals/index.md) | road-dispatch.arrivals.index, road-dispatch.arrivals.create | ✅ |
| [road-dispatch/unloads/create](./road-dispatch/unloads/create.md) | road-dispatch.unloads.index, road-dispatch.unloads.create | ✅ |
| [road-dispatch/unloads/index](./road-dispatch/unloads/index.md) | road-dispatch.unloads.index, road-dispatch.unloads.create | ✅ |


