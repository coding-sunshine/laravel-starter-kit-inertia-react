Rake Management System – Complete System Architecture

========================================================
1. MASTER DATA MODULE
========================================================

users
vehicles
sidings
routes
freight_rate_master


========================================================
2. MINE OPERATIONS MODULE
========================================================

vehicles
└── vehicle_coal_site
    ├── vehicle_weighment_data
    └── vehicle_trips


========================================================
3. TRANSIT MONITORING MODULE
========================================================

vehicle_trips
├── gps_tracking_logs
├── trip_stoppages
├── route_deviations
└── patrol_reports


========================================================
4. SIDING OPERATIONS MODULE
========================================================

sidings
├── vehicle_unloads
│   └── vehicle_unload_weighments
│
├── coal_stock
│
└── indents
    └── rakes


========================================================
5. RAKE OPERATIONS MODULE
========================================================

rakes
├── rake_loads
│   ├── rake_load_steps
│   └── rake_weighments
│       └── rake_wagon_weighments
│
├── rake_wagons
│   └── rake_wagon_loading
│
└── rake_extra_penalties


========================================================
6. RAILWAY RECEIPT (RR) MODULE
========================================================

rakes
├── rr_predictions
│
└── rr_actuals
    ├── rr_wagon_details
    └── rr_additional_charges


========================================================
7. COMPLETE END-TO-END FLOW
========================================================

vehicles
└── vehicle_coal_site
    ├── vehicle_weighment_data
    └── vehicle_trips
        ├── gps_tracking_logs
        ├── trip_stoppages
        ├── route_deviations
        └── patrol_reports
            ↓
sidings
└── vehicle_unloads
    ├── vehicle_unload_weighments
    └── coal_stock
        ↓
indents
└── rakes
    ├── rake_wagons
    │   └── rake_wagon_loading
    │
    ├── rake_loads
    │   ├── rake_load_steps
    │   └── rake_weighments
    │       └── rake_wagon_weighments
    │
    ├── rake_extra_penalties
    │
    ├── rr_predictions
    │
    └── rr_actuals
        ├── rr_wagon_details
        └── rr_additional_charges


========================================================
8. DESIGN PRINCIPLES
========================================================

1. One vehicle can enter mine multiple times.
2. One mine entry can generate multiple weighment attempts.
3. One vehicle trip generates multiple GPS logs and deviation records.
4. One siding unload generates multiple unload weighments.
5. Coal stock is maintained as a ledger (transaction-based).
6. One indent generates one rake.
7. One rake contains multiple wagons.
8. One rake loading session can have multiple weighment attempts.
9. Demurrage(Additional time penalty) is calculated per rake load session.
10. RR prediction and RR actual are stored separately for reconciliation.
