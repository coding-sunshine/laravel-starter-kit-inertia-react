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
| [dashboard](./dashboard.md) | dashboard, dashboard.executive-yesterday-data | ✅ |
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
| [welcome](./welcome.md) | N/A | ✅ |
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
| [indents/index](./indents/index.md) | indents.index, indents.import | ✅ |
| [mobile/SidingDashboard](./mobile/SidingDashboard.md) | N/A | ✅ |
| [penalties/index](./penalties/index.md) | penalties.index, penalties.analytics | ✅ |
| [railway-receipts/create](./railway-receipts/create.md) | railway-receipts.index, railway-receipts.upload | ✅ |
| [railway-receipts/index](./railway-receipts/index.md) | railway-receipts.index, railway-receipts.upload | ✅ |
| [railway-receipts/show](./railway-receipts/show.md) | railway-receipts.index, railway-receipts.upload | ✅ |
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
| [indents/create](./indents/create.md) | indents.index, indents.import | ✅ |
| [indents/edit](./indents/edit.md) | indents.index, indents.import | ✅ |
| [indents/show](./indents/show.md) | indents.index, indents.import | ✅ |
| [penalties/analytics](./penalties/analytics.md) | penalties.index, penalties.analytics | ✅ |
| [rakes/create-from-indent](./rakes/create-from-indent.md) | indents.index, indents.import | ✅ |
| [road-dispatch/arrivals/show](./road-dispatch/arrivals/show.md) | road-dispatch.arrivals.index, road-dispatch.arrivals.create | ✅ |
| [road-dispatch/components/WeighmentHistory](./road-dispatch/components/WeighmentHistory.md) | N/A | ✅ |
| [road-dispatch/stepper/track-timeline](./road-dispatch/stepper/track-timeline.md) | N/A | ✅ |
| [road-dispatch/unloads/show](./road-dispatch/unloads/show.md) | road-dispatch.unloads.index, road-dispatch.unloads.create | ✅ |
| [MasterData/DistanceMatrix/Create](./MasterData/DistanceMatrix/Create.md) | master-data.master-data.distance-matrix.index, master-data.master-data.distance-matrix.create | ✅ |
| [MasterData/DistanceMatrix/Index](./MasterData/DistanceMatrix/Index.md) | master-data.master-data.distance-matrix.index, master-data.master-data.distance-matrix.create | ✅ |
| [MasterData/Loaders/Create](./MasterData/Loaders/Create.md) | master-data.loaders.index, master-data.loaders.create | ✅ |
| [MasterData/Loaders/Index](./MasterData/Loaders/Index.md) | master-data.loaders.index, master-data.loaders.create | ✅ |
| [MasterData/PenaltyTypes/Create](./MasterData/PenaltyTypes/Create.md) | master-data.penalty-types.index, master-data.penalty-types.create | ✅ |
| [MasterData/PenaltyTypes/Index](./MasterData/PenaltyTypes/Index.md) | master-data.penalty-types.index, master-data.penalty-types.create | ✅ |
| [MasterData/PowerPlants/Create](./MasterData/PowerPlants/Create.md) | master-data.power-plants.index, master-data.power-plants.create | ✅ |
| [MasterData/PowerPlants/Edit](./MasterData/PowerPlants/Edit.md) | master-data.power-plants.index, master-data.power-plants.create | ✅ |
| [MasterData/PowerPlants/Index](./MasterData/PowerPlants/Index.md) | master-data.power-plants.index, master-data.power-plants.create | ✅ |
| [MasterData/PowerPlants/Show](./MasterData/PowerPlants/Show.md) | master-data.power-plants.index, master-data.power-plants.create | ✅ |
| [MasterData/SectionTimers/Create](./MasterData/SectionTimers/Create.md) | master-data.section-timers.index, master-data.section-timers.create | ✅ |
| [MasterData/SectionTimers/Index](./MasterData/SectionTimers/Index.md) | master-data.section-timers.index, master-data.section-timers.create | ✅ |
| [MasterData/Sidings/Index](./MasterData/Sidings/Index.md) | master-data.sidings.index, master-data.sidings.create | ✅ |
| [rakes/load](./rakes/load.md) | rakes.load.show, rakes.load.confirm-placement | ✅ |
| [road-dispatch/daily-vehicle-entries/index](./road-dispatch/daily-vehicle-entries/index.md) | road-dispatch.daily-vehicle-entries.index, road-dispatch.daily-vehicle-entries.store | ✅ |
| [road-dispatch/daily-vehicle-entries/shift-tabs](./road-dispatch/daily-vehicle-entries/shift-tabs.md) | N/A | ✅ |
| [road-dispatch/daily-vehicle-entries/shift-report-dialog](./road-dispatch/daily-vehicle-entries/shift-report-dialog.md) | N/A | ✅ |
| [road-dispatch/daily-vehicle-entries/vehicle-entry-row](./road-dispatch/daily-vehicle-entries/vehicle-entry-row.md) | N/A | ✅ |
| [road-dispatch/daily-vehicle-entries/vehicle-entry-table](./road-dispatch/daily-vehicle-entries/vehicle-entry-table.md) | N/A | ✅ |
| [VehicleDispatch/DPRTab](./VehicleDispatch/DPRTab.md) | N/A | ✅ |
| [VehicleDispatch/ImportPreviewCard](./VehicleDispatch/ImportPreviewCard.md) | N/A | ✅ |
| [VehicleDispatch/Index](./VehicleDispatch/Index.md) | vehicle-dispatch.index, vehicle-dispatch.reconciliation-report | ✅ |
| [VehicleDispatch/MainDataTab](./VehicleDispatch/MainDataTab.md) | N/A | ✅ |
| [VehicleDispatch/VehicleDispatchTable](./VehicleDispatch/VehicleDispatchTable.md) | N/A | ✅ |
| [VehicleDispatch/VehicleDispatchTabs](./VehicleDispatch/VehicleDispatchTabs.md) | N/A | ✅ |
| [VehicleWorkorders/Edit](./VehicleWorkorders/Edit.md) | vehicle-workorders.index, vehicle-workorders.export | ✅ |
| [VehicleWorkorders/Index](./VehicleWorkorders/Index.md) | vehicle-workorders.index, vehicle-workorders.export | ✅ |
| [MasterData/OpeningCoalStock/Edit](./MasterData/OpeningCoalStock/Edit.md) | master-data.opening-coal-stock.index, master-data.opening-coal-stock.edit | ✅ |
| [MasterData/OpeningCoalStock/Index](./MasterData/OpeningCoalStock/Index.md) | master-data.opening-coal-stock.index, master-data.opening-coal-stock.edit | ✅ |
| [MasterData/SectionTimers/Edit](./MasterData/SectionTimers/Edit.md) | master-data.section-timers.index, master-data.section-timers.create | ✅ |
| [MasterData/SectionTimers/Show](./MasterData/SectionTimers/Show.md) | master-data.section-timers.index, master-data.section-timers.create | ✅ |
| [MasterData/ShiftTimings/Edit](./MasterData/ShiftTimings/Edit.md) | master-data.shift-timings.index, master-data.shift-timings.edit | ✅ |
| [MasterData/ShiftTimings/Index](./MasterData/ShiftTimings/Index.md) | master-data.shift-timings.index, master-data.shift-timings.edit | ✅ |
| [historical/mines/index](./historical/mines/index.md) | historical.mines.index, historical.mines.store | ✅ |
| [historical/railway-siding/index](./historical/railway-siding/index.md) | historical.railway-siding.index, historical.railway-siding.export | ✅ |
| [production/edit](./production/edit.md) | production.coal.index, production.coal.store | ✅ |
| [production/index](./production/index.md) | production.coal.index, production.coal.store | ✅ |
| [railway-siding-empty-weighment/index](./railway-siding-empty-weighment/index.md) | railway-siding-empty-weighment.index, railway-siding-empty-weighment.store | ✅ |
| [railway-siding-empty-weighment/shift-tabs](./railway-siding-empty-weighment/shift-tabs.md) | N/A | ✅ |
| [railway-siding-empty-weighment/vehicle-entry-row](./railway-siding-empty-weighment/vehicle-entry-row.md) | N/A | ✅ |
| [railway-siding-empty-weighment/vehicle-entry-table](./railway-siding-empty-weighment/vehicle-entry-table.md) | N/A | ✅ |
| [weighments/index](./weighments/index.md) | weighments.index, weighments.show | ✅ |
| [weighments/show](./weighments/show.md) | weighments.index, weighments.show | ✅ |
| [MasterData/DistanceMatrix/Edit](./MasterData/DistanceMatrix/Edit.md) | master-data.master-data.distance-matrix.index, master-data.master-data.distance-matrix.create | ✅ |
| [MasterData/DistanceMatrix/Show](./MasterData/DistanceMatrix/Show.md) | master-data.master-data.distance-matrix.index, master-data.master-data.distance-matrix.create | ✅ |
| [MasterData/Loaders/Edit](./MasterData/Loaders/Edit.md) | master-data.loaders.index, master-data.loaders.create | ✅ |
| [MasterData/Loaders/Show](./MasterData/Loaders/Show.md) | master-data.loaders.index, master-data.loaders.create | ✅ |
| [MasterData/PenaltyTypes/Edit](./MasterData/PenaltyTypes/Edit.md) | master-data.penalty-types.index, master-data.penalty-types.create | ✅ |
| [MasterData/PenaltyTypes/Show](./MasterData/PenaltyTypes/Show.md) | master-data.penalty-types.index, master-data.penalty-types.create | ✅ |
| [MasterData/Sidings/Create](./MasterData/Sidings/Create.md) | master-data.sidings.index, master-data.sidings.create | ✅ |
| [MasterData/Sidings/Edit](./MasterData/Sidings/Edit.md) | master-data.sidings.index, master-data.sidings.create | ✅ |
| [MasterData/Sidings/Show](./MasterData/Sidings/Show.md) | master-data.sidings.index, master-data.sidings.create | ✅ |
| [VehicleWorkorders/Create](./VehicleWorkorders/Create.md) | vehicle-workorders.index, vehicle-workorders.export | ✅ |
| MasterData/DailyStockDetails/Create | master-data.daily-stock-details.index, master-data.daily-stock-details.export | ❌ |
| MasterData/DailyStockDetails/Edit | master-data.daily-stock-details.index, master-data.daily-stock-details.export | ❌ |
| MasterData/DailyStockDetails/Index | master-data.daily-stock-details.index, master-data.daily-stock-details.export | ❌ |
| [rake-loader/index](docs/developer/frontend/pages/rake-loader/index.md) | rake-loader.index, rake-loader.rakes.loading | ✅ |
| [MasterData/StockLedger/Index](./MasterData/StockLedger/Index.md) | master-data.stock-ledger.index, master-data.stock-ledger.stock-report | ✅ |
| [MasterData/StockLedger/stock-report-dialog](./MasterData/StockLedger/stock-report-dialog.md) | N/A | ✅ |
| rake-loader/loading | rake-loader.index, rake-loader.rakes.loading | ❌ |
| siding-pre-indent-reports/create | siding-pre-indent-reports.index, siding-pre-indent-reports.create | ❌ |
| siding-pre-indent-reports/edit | siding-pre-indent-reports.index, siding-pre-indent-reports.create | ❌ |
| siding-pre-indent-reports/index | siding-pre-indent-reports.index, siding-pre-indent-reports.create | ❌ |
| siding-pre-indent-reports/show | siding-pre-indent-reports.index, siding-pre-indent-reports.create | ❌ |
| control-room/index | control-room.index, control-room.show | ❌ |
| control-room/show | control-room.index, control-room.show | ❌ |
| dashboard/ExecutiveOverview | N/A | ❌ |
| dashboard/LoaderOverloading | N/A | ❌ |
| dashboard/Operations | N/A | ❌ |
| dashboard/PenaltyControl | N/A | ❌ |
| dashboard/PowerPlant | N/A | ❌ |
| dashboard/RakePerformance | N/A | ❌ |
| dashboard/SidingOverview | N/A | ❌ |
| sidings/monitor | sidings.monitor | ❌ |
| sidings/quick-placement | sidings.quick-placement.show, sidings.quick-placement.store | ❌ |
| TransportWorkOrderRegistrations/Create | vehicle-workorders.transport-registrations.create, vehicle-workorders.transport-registrations.store | ❌ |
| TransportWorkOrderRegistrations/Edit | vehicle-workorders.transport-registrations.create, vehicle-workorders.transport-registrations.store | ❌ |
| VehicleWorkorders/TransportRegistrationsTable | N/A | ❌ |
| control-panel-v2/index | control-panel.index, control-panel.wagon-timeline | ❌ |
| control-panel-v2/siding | control-panel.index, control-panel.wagon-timeline | ❌ |
| dashboard/date-wise-dispatch-section | N/A | ❌ |
| dashboard/shared | N/A | ❌ |
| [manager-brief/index](../frontend/pages/manager-brief.md) | manager-brief.index, manager-brief.refresh | ✅ |
| DailySidingDispatchRollups/Index | daily-siding-dispatch-rollups.index, daily-siding-dispatch-rollups.recalculate | ❌ |
| DailyVehicleEntryRollups/Index | daily-vehicle-entry-rollups.index, daily-vehicle-entry-rollups.recalculate | ❌ |
| VehicleDispatch/dispatch-reconciliation-dialog | N/A | ❌ |


