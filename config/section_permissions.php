<?php

declare(strict_types=1);

/**
 * Section-based permissions aligned with the app sidebar.
 * Permission names: sections.{slug}.{action}
 * Used for nav visibility and route authorization (see route_to_permission map).
 */
return [

    'sections' => [
        [
            'slug' => 'dashboard',
            'label' => 'Dashboard',
            'actions' => ['view'],
        ],
        [
            'slug' => 'power_plants',
            'label' => 'Power Plants',
            'actions' => ['view', 'create', 'update', 'delete'],
        ],
        [
            'slug' => 'sidings',
            'label' => 'Sidings',
            'actions' => ['view', 'create', 'update', 'delete'],
        ],
        [
            'slug' => 'loaders',
            'label' => 'Loaders',
            'actions' => ['view', 'create', 'update', 'delete'],
        ],
        [
            'slug' => 'penalty_types',
            'label' => 'Penalty Types',
            'actions' => ['view', 'create', 'update', 'delete'],
        ],
        [
            'slug' => 'section_timers',
            'label' => 'Section Timers',
            'actions' => ['view', 'create', 'update', 'delete'],
        ],
        [
            'slug' => 'shift_timings',
            'label' => 'Shift Timings',
            'actions' => ['view', 'create', 'update', 'delete'],
        ],
        [
            'slug' => 'opening_coal_stock',
            'label' => 'Opening Coal Stock',
            'actions' => ['view', 'update'],
        ],
        [
            'slug' => 'distance_matrix',
            'label' => 'Distance Matrix',
            'actions' => ['view', 'create', 'update', 'delete'],
        ],
        [
            'slug' => 'billing',
            'label' => 'Billing',
            'actions' => ['view', 'manage'],
        ],
        [
            'slug' => 'rakes',
            'label' => 'Rakes',
            'actions' => ['view', 'create', 'update', 'delete', 'upload'],
        ],
        [
            'slug' => 'indents',
            'label' => 'Indents',
            'actions' => ['view', 'create', 'update', 'delete'],
        ],
        [
            'slug' => 'railway_siding_record_data',
            'label' => 'Railway Siding Record Data',
            'actions' => ['view', 'create', 'update', 'delete'],
        ],
        [
            'slug' => 'railway_siding_empty_weighment',
            'label' => 'Railway Siding Empty Weighment',
            'actions' => ['view', 'create', 'update', 'delete'],
        ],
        [
            'slug' => 'production_coal',
            'label' => 'Production - Coal',
            'actions' => ['view', 'create', 'update', 'delete'],
        ],
        [
            'slug' => 'production_ob',
            'label' => 'Production - OB',
            'actions' => ['view', 'create', 'update', 'delete'],
        ],
        [
            'slug' => 'mines_dispatch_data',
            'label' => 'Mines Dispatch Data',
            'actions' => ['view', 'upload'],
        ],
        [
            'slug' => 'transport',
            'label' => 'Transport',
            'actions' => ['view', 'update'],
        ],
        [
            'slug' => 'railway_receipts',
            'label' => 'Railway Receipts',
            'actions' => ['view', 'upload'],
        ],
        [
            'slug' => 'penalties',
            'label' => 'Penalties',
            'actions' => ['view', 'update'],
        ],
        [
            'slug' => 'alerts',
            'label' => 'Alerts',
            'actions' => ['view', 'update'],
        ],
        [
            'slug' => 'reconciliation',
            'label' => 'Reconciliation',
            'actions' => ['view', 'create'],
        ],
        [
            'slug' => 'weighments',
            'label' => 'Weighments',
            'actions' => ['view', 'upload'],
        ],
        [
            'slug' => 'historical_mines',
            'label' => 'Historical Mines',
            'actions' => ['view', 'create', 'update', 'delete'],
        ],
        [
            'slug' => 'historical_railway_siding',
            'label' => 'Historical Railway Siding',
            'actions' => ['view', 'create', 'update', 'delete'],
        ],
        [
            'slug' => 'reports',
            'label' => 'Reports',
            'actions' => ['view', 'generate'],
        ],
        [
            'slug' => 'changelog',
            'label' => 'Changelog',
            'actions' => ['view'],
        ],
        [
            'slug' => 'help',
            'label' => 'Help',
            'actions' => ['view'],
        ],
        [
            'slug' => 'contact',
            'label' => 'Contact',
            'actions' => ['create'],
        ],
    ],

    /*
     * Map route names to required section permission.
     * Middleware uses this to authorize requests by section permission instead of route name.
     * Keys are exact route names; first match wins (no wildcards for simplicity).
     */
    'route_to_permission' => [
        // Dashboard
        'dashboard' => 'sections.dashboard.view',

        // Master Data
        'master-data.power-plants.index' => 'sections.power_plants.view',
        'master-data.power-plants.show' => 'sections.power_plants.view',
        'master-data.power-plants.create' => 'sections.power_plants.create',
        'master-data.power-plants.store' => 'sections.power_plants.create',
        'master-data.power-plants.edit' => 'sections.power_plants.update',
        'master-data.power-plants.update' => 'sections.power_plants.update',
        'master-data.power-plants.destroy' => 'sections.power_plants.delete',

        'master-data.sidings.index' => 'sections.sidings.view',
        'master-data.sidings.show' => 'sections.sidings.view',
        'master-data.sidings.create' => 'sections.sidings.create',
        'master-data.sidings.store' => 'sections.sidings.create',
        'master-data.sidings.edit' => 'sections.sidings.update',
        'master-data.sidings.update' => 'sections.sidings.update',
        'master-data.sidings.destroy' => 'sections.sidings.delete',

        'master-data.loaders.index' => 'sections.loaders.view',
        'master-data.loaders.show' => 'sections.loaders.view',
        'master-data.loaders.create' => 'sections.loaders.create',
        'master-data.loaders.store' => 'sections.loaders.create',
        'master-data.loaders.edit' => 'sections.loaders.update',
        'master-data.loaders.update' => 'sections.loaders.update',
        'master-data.loaders.destroy' => 'sections.loaders.delete',

        'master-data.penalty-types.index' => 'sections.penalty_types.view',
        'master-data.penalty-types.show' => 'sections.penalty_types.view',
        'master-data.penalty-types.create' => 'sections.penalty_types.create',
        'master-data.penalty-types.store' => 'sections.penalty_types.create',
        'master-data.penalty-types.edit' => 'sections.penalty_types.update',
        'master-data.penalty-types.update' => 'sections.penalty_types.update',
        'master-data.penalty-types.destroy' => 'sections.penalty_types.delete',

        'master-data.section-timers.index' => 'sections.section_timers.view',
        'master-data.section-timers.show' => 'sections.section_timers.view',
        'master-data.section-timers.create' => 'sections.section_timers.create',
        'master-data.section-timers.store' => 'sections.section_timers.create',
        'master-data.section-timers.edit' => 'sections.section_timers.update',
        'master-data.section-timers.update' => 'sections.section_timers.update',
        'master-data.section-timers.destroy' => 'sections.section_timers.delete',

        'master-data.shift-timings.index' => 'sections.shift_timings.view',
        'master-data.shift-timings.edit' => 'sections.shift_timings.update',
        'master-data.shift-timings.update' => 'sections.shift_timings.update',

        'master-data.opening-coal-stock.index' => 'sections.opening_coal_stock.view',
        'master-data.opening-coal-stock.edit' => 'sections.opening_coal_stock.update',
        'master-data.opening-coal-stock.update' => 'sections.opening_coal_stock.update',

        'master-data.distance-matrix.index' => 'sections.distance_matrix.view',
        'master-data.distance-matrix.show' => 'sections.distance_matrix.view',
        'master-data.distance-matrix.create' => 'sections.distance_matrix.create',
        'master-data.distance-matrix.store' => 'sections.distance_matrix.create',
        'master-data.distance-matrix.edit' => 'sections.distance_matrix.update',
        'master-data.distance-matrix.update' => 'sections.distance_matrix.update',
        'master-data.distance-matrix.destroy' => 'sections.distance_matrix.delete',

        // Billing
        'billing.index' => 'sections.billing.view',
        'billing.credits.index' => 'sections.billing.view',
        'billing.credits.purchase' => 'sections.billing.manage',
        'billing.credits.checkout.lemon-squeezy' => 'sections.billing.manage',
        'billing.invoices.index' => 'sections.billing.view',
        'billing.invoices.download' => 'sections.billing.view',

        // Rakes
        'rakes.index' => 'sections.rakes.view',
        'rakes.show' => 'sections.rakes.view',
        'rakes.edit' => 'sections.rakes.update',
        'rakes.update' => 'sections.rakes.update',
        'rakes.destroy' => 'sections.rakes.delete',
        'rakes.generate-wagons' => 'sections.rakes.update',
        'rakes.loading.start' => 'sections.rakes.update',
        'rakes.loading.reset' => 'sections.rakes.update',
        'rakes.loading.stop' => 'sections.rakes.update',
        'rakes.loading-times.update' => 'sections.rakes.update',
        'rakes.wagons.update' => 'sections.rakes.update',
        'rakes.wagons.bulk-update' => 'sections.rakes.update',
        'rakes.txr.update' => 'sections.rakes.update',
        'rakes.txr.start' => 'sections.rakes.update',
        'rakes.txr.end' => 'sections.rakes.update',
        'rakes.txr.unfit-logs' => 'sections.rakes.update',
        'rakes.txr.upload-note' => 'sections.rakes.upload',
        'rakes.load.show' => 'sections.rakes.view',
        'rakes.load.confirm-placement' => 'sections.rakes.update',
        'rakes.load.wagon' => 'sections.rakes.update',
        'rakes.load.wagons' => 'sections.rakes.update',
        'rakes.load.wagon-rows.store' => 'sections.rakes.update',
        'rakes.load.wagon-rows.update' => 'sections.rakes.update',
        'rakes.load.wagon-rows.destroy' => 'sections.rakes.update',
        'rakes.load.guard-inspection' => 'sections.rakes.update',
        'rakes.load.confirm-dispatch' => 'sections.rakes.update',
        'rakes.weighments.store' => 'sections.rakes.upload',
        'rakes.weighments.destroy' => 'sections.rakes.update',
        'rakes.comparison' => 'sections.rakes.view',
        'rakes.guard-inspection.store' => 'sections.rakes.update',
        'txr.unfit-wagons.store' => 'sections.rakes.update',

        // Indents
        'indents.index' => 'sections.indents.view',
        'indents.show' => 'sections.indents.view',
        'indents.create' => 'sections.indents.create',
        'indents.store' => 'sections.indents.create',
        'indents.edit' => 'sections.indents.update',
        'indents.update' => 'sections.indents.update',
        'indents.import' => 'sections.indents.create',
        'indents.pdf' => 'sections.indents.view',
        'indents.create-rake' => 'sections.indents.create',
        'indents.store-rake' => 'sections.indents.create',

        // Railway Siding Record Data (Road Dispatch)
        'road-dispatch.daily-vehicle-entries.index' => 'sections.railway_siding_record_data.view',
        'road-dispatch.daily-vehicle-entries.store' => 'sections.railway_siding_record_data.create',
        'road-dispatch.daily-vehicle-entries.update' => 'sections.railway_siding_record_data.update',
        'road-dispatch.daily-vehicle-entries.destroy' => 'sections.railway_siding_record_data.delete',
        'road-dispatch.daily-vehicle-entries.complete' => 'sections.railway_siding_record_data.update',
        'road-dispatch.daily-vehicle-entries.export' => 'sections.railway_siding_record_data.view',
        'road-dispatch.vehicle-workorders.lookup' => 'sections.railway_siding_record_data.view',
        'road-dispatch.arrivals.index' => 'sections.railway_siding_record_data.view',
        'road-dispatch.arrivals.create' => 'sections.railway_siding_record_data.create',
        'road-dispatch.arrivals.store' => 'sections.railway_siding_record_data.create',
        'road-dispatch.arrivals.show' => 'sections.railway_siding_record_data.view',
        'road-dispatch.arrivals.unload' => 'sections.railway_siding_record_data.update',
        'road-dispatch.unloads.index' => 'sections.railway_siding_record_data.view',
        'road-dispatch.unloads.create' => 'sections.railway_siding_record_data.create',
        'road-dispatch.unloads.store' => 'sections.railway_siding_record_data.create',
        'road-dispatch.unloads.show' => 'sections.railway_siding_record_data.view',
        'road-dispatch.unloads.gross-weighment' => 'sections.railway_siding_record_data.update',
        'road-dispatch.unloads.start-unload' => 'sections.railway_siding_record_data.update',
        'road-dispatch.unloads.tare-weighment' => 'sections.railway_siding_record_data.update',
        'road-dispatch.unloads.complete' => 'sections.railway_siding_record_data.update',
        'road-dispatch.unloads.confirm' => 'sections.railway_siding_record_data.update',

        // Railway Siding Empty Weighment
        'railway-siding-empty-weighment.index' => 'sections.railway_siding_empty_weighment.view',
        'railway-siding-empty-weighment.store' => 'sections.railway_siding_empty_weighment.create',
        'railway-siding-empty-weighment.update' => 'sections.railway_siding_empty_weighment.update',
        'railway-siding-empty-weighment.destroy' => 'sections.railway_siding_empty_weighment.delete',
        'railway-siding-empty-weighment.complete' => 'sections.railway_siding_empty_weighment.update',
        'railway-siding-empty-weighment.export' => 'sections.railway_siding_empty_weighment.view',

        // Production Coal / OB
        'production.coal.index' => 'sections.production_coal.view',
        'production.coal.store' => 'sections.production_coal.create',
        'production.coal.edit' => 'sections.production_coal.update',
        'production.coal.update' => 'sections.production_coal.update',
        'production.coal.destroy' => 'sections.production_coal.delete',
        'production.ob.index' => 'sections.production_ob.view',
        'production.ob.store' => 'sections.production_ob.create',
        'production.ob.edit' => 'sections.production_ob.update',
        'production.ob.update' => 'sections.production_ob.update',
        'production.ob.destroy' => 'sections.production_ob.delete',

        // Mines Dispatch Data
        'vehicle-dispatch.index' => 'sections.mines_dispatch_data.view',
        'vehicle-dispatch.update' => 'sections.mines_dispatch_data.upload',
        'vehicle-dispatch.import' => 'sections.mines_dispatch_data.upload',
        'vehicle-dispatch.save' => 'sections.mines_dispatch_data.upload',
        'dispatch-reports.generate' => 'sections.mines_dispatch_data.view',

        // Transport (Vehicle Workorders)
        'vehicle-workorders.index' => 'sections.transport.view',
        'vehicle-workorders.edit' => 'sections.transport.update',
        'vehicle-workorders.update' => 'sections.transport.update',

        // Railway Receipts
        'railway-receipts.index' => 'sections.railway_receipts.view',
        'railway-receipts.import' => 'sections.railway_receipts.upload',
        'railway-receipts.upload' => 'sections.railway_receipts.upload',
        'railway-receipts.create' => 'sections.railway_receipts.upload',
        'railway-receipts.store' => 'sections.railway_receipts.upload',
        'railway-receipts.show' => 'sections.railway_receipts.view',
        'railway-receipts.pdf' => 'sections.railway_receipts.view',
        'railway-receipts.update' => 'sections.railway_receipts.upload',
        'railway-receipts.rakes' => 'sections.railway_receipts.view',

        // Penalties
        'penalties.index' => 'sections.penalties.view',
        'penalties.analytics' => 'sections.penalties.view',
        'penalties.update' => 'sections.penalties.update',
        'penalties.dispute-recommendation' => 'sections.penalties.update',

        // Alerts
        'alerts.index' => 'sections.alerts.view',
        'alerts.resolve' => 'sections.alerts.update',

        // Reconciliation
        'reconciliation.index' => 'sections.reconciliation.view',
        'reconciliation.show' => 'sections.reconciliation.view',
        'reconciliation.power-plant-receipts.index' => 'sections.reconciliation.view',
        'reconciliation.power-plant-receipts.create' => 'sections.reconciliation.create',
        'reconciliation.power-plant-receipts.store' => 'sections.reconciliation.create',

        // Reports
        'reports.index' => 'sections.reports.view',
        'reports.generate' => 'sections.reports.generate',

        // Weighments
        'weighments.index' => 'sections.weighments.view',
        'weighments.show' => 'sections.weighments.view',
        'weighments.import' => 'sections.weighments.upload',

        // Historical Mines (monthly mines data)
        'historical.mines.index' => 'sections.historical_mines.view',
        'historical.mines.store' => 'sections.historical_mines.create',
        'historical.mines.update' => 'sections.historical_mines.update',
        'historical.mines.destroy' => 'sections.historical_mines.delete',

        // Historical Railway Siding (historical rake data)
        'historical.railway-siding.index' => 'sections.historical_railway_siding.view',
        'historical.railway-siding.store' => 'sections.historical_railway_siding.create',
        'historical.railway-siding.update' => 'sections.historical_railway_siding.update',
        'historical.railway-siding.destroy' => 'sections.historical_railway_siding.delete',

        // Changelog / Help / Contact (public or feature-gated)
        'changelog.index' => 'sections.changelog.view',
        'help.index' => 'sections.help.view',
        'help.show' => 'sections.help.view',
        'help.rate' => 'sections.help.view',
        'contact.create' => 'sections.contact.create',
        'contact.store' => 'sections.contact.create',
    ],

    /**
     * Nav permission: which section permission grants visibility for each sidebar item.
     * Keys match sidebar href or a logical key; used by frontend to set NavItem.permission.
     */
    'nav_permission' => [
        'dashboard' => 'sections.dashboard.view',
        'power_plants' => 'sections.power_plants.view',
        'sidings' => 'sections.sidings.view',
        'loaders' => 'sections.loaders.view',
        'penalty_types' => 'sections.penalty_types.view',
        'section_timers' => 'sections.section_timers.view',
        'shift_timings' => 'sections.shift_timings.view',
        'opening_coal_stock' => 'sections.opening_coal_stock.view',
        'distance_matrix' => 'sections.distance_matrix.view',
        'billing' => 'sections.billing.view',
        'rakes' => 'sections.rakes.view',
        'indents' => 'sections.indents.view',
        'railway_siding_record_data' => 'sections.railway_siding_record_data.view',
        'railway_siding_empty_weighment' => 'sections.railway_siding_empty_weighment.view',
        'production_coal' => 'sections.production_coal.view',
        'production_ob' => 'sections.production_ob.view',
        'mines_dispatch_data' => 'sections.mines_dispatch_data.view',
        'transport' => 'sections.transport.view',
        'railway_receipts' => 'sections.railway_receipts.view',
        'penalties' => 'sections.penalties.view',
        'alerts' => 'sections.alerts.view',
        'reconciliation' => 'sections.reconciliation.view',
        'weighments' => 'sections.weighments.view',
        'reports' => 'sections.reports.view',
        'changelog' => 'sections.changelog.view',
        'help' => 'sections.help.view',
        'contact' => 'sections.contact.create',
        'historical_mines' => 'sections.historical_mines.view',
        'historical_railway_siding' => 'sections.historical_railway_siding.view',
    ],
];
