<?php

declare(strict_types=1);

return [
    /*
    |--------------------------------------------------------------------------
    | Demurrage rate (₹ per MT per hour)
    |--------------------------------------------------------------------------
    | Railway demurrage is charged when rakes exceed free time. This value is
    | used as ₹ per metric tonne (MT) per hour in the formula:
    |   demurrage = hours_over × weight_mt × demurrage_rate_per_mt_hour
    | Set RRMCS_DEMURRAGE_RATE_PER_MT_HOUR in .env accordingly.
    |
    | If the railway provides a total-per-hour figure (e.g. "₹15,440 + GST per
    | hour" for the whole rake), convert to per-MT-hour by dividing by a
    | typical rake weight (e.g. 3500 MT): 15440 / 3500 ≈ 4.41 ₹/MT/h. The app
    | does not change this conversion; confirm the exact formula with product.
    */
    'demurrage_rate_per_mt_hour' => (float) env('RRMCS_DEMURRAGE_RATE_PER_MT_HOUR', 50),

    /*
    |--------------------------------------------------------------------------
    | Default free time minutes for rakes
    |--------------------------------------------------------------------------
    | Default free time in minutes allocated to each rake before demurrage
    | charges start. This can be overridden per rake as needed.
    */
    'default_free_time_minutes' => (int) env('RRMCS_DEFAULT_FREE_TIME_MINUTES', 180),

    /*
    |--------------------------------------------------------------------------
    | PRD / real data import paths (Excel)
    |--------------------------------------------------------------------------
    | Paths relative to project base for seeding historical rake/penalty data.
    | Used by RealDataImportSeeder. Set to null to skip a source.
    */
    'prd_import' => [
        'base_path' => 'prd/docs',
        'memory_limit' => env('RRMCS_IMPORT_MEMORY_LIMIT', '512M'),
        'pakur_monthly' => 'Rake Management Application - references/Rake Data Nov19-Dec24/RAKE NOV-19 TO DEC-24 (Pakur).xlsx',
        'dumka_loading' => 'Requirements/Rake Loading Data Dumka.xlsx',
        'kurwa_loading' => 'Requirements/RAKE LOADING DATA KURWA.xlsx',
        'imwb_sensor' => 'Requirements/IMWB - LOAD SENSOR REPORT.xlsx',
        'reports_draft' => 'Rake Management Application - references/Scope of Work/Rake Management Reports (Draft Only).xlsx',
    ],

    /*
    |--------------------------------------------------------------------------
    | Default siding for IMWB (load sensor) import when not determinable
    |--------------------------------------------------------------------------
    */
    'imwb_default_siding_code' => env('RRMCS_IMWB_DEFAULT_SIDING', 'DUMK'),

    /*
    |--------------------------------------------------------------------------
    | Railway Receipt: strict Station From validation (rake-linked uploads)
    |--------------------------------------------------------------------------
    | When a rake's siding uses one of these station_code values (uppercase in
    | DB, compared case-insensitively), the parsed PDF must include Station From
    | and it must match that siding's station_code or code after alias mapping.
    | Other sidings (e.g. Kurwa, Pakur) skip "from" validation — PDF headers are
    | not reliable enough there yet.
    */
    'rr_strict_from_station_station_codes' => ['DMK'],

    /*
    |--------------------------------------------------------------------------
    | Map PDF/header station tokens to canonical codes (uppercase keys)
    |--------------------------------------------------------------------------
    */
    'rr_from_station_pdf_code_aliases' => [
        'DUMK' => 'DMK',
    ],
];
