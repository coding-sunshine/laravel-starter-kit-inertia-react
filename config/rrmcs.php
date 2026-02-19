<?php

declare(strict_types=1);

return [
    /*
    |--------------------------------------------------------------------------
    | Demurrage rate (₹ per MT per hour)
    |--------------------------------------------------------------------------
    | Railway demurrage is charged when rakes exceed free time. This rate is
    | used for calculations, alerts, and transparent breakdowns in the UI.
    */
    'demurrage_rate_per_mt_hour' => (float) env('RRMCS_DEMURRAGE_RATE_PER_MT_HOUR', 50),

    /*
    |--------------------------------------------------------------------------
    | PRD / real data import paths (Excel)
    |--------------------------------------------------------------------------
    | Paths relative to project base for seeding historical rake/penalty data.
    | Used by RealDataImportSeeder. Set to null to skip a source.
    */
    'prd_import' => [
        'base_path' => 'prd/docs',
        'pakur_monthly' => 'Rake Management Application - references/Rake Data Nov19-Dec24/RAKE NOV-19 TO DEC-24 (Pakur).xlsx',
        'dumka_loading' => 'Requirements/Rake Loading Data Dumka.xlsx',
        'kurwa_loading' => 'Requirements/RAKE LOADING DATA KURWA.xlsx',
        'reports_draft' => 'Rake Management Application - references/Scope of Work/Rake Management Reports (Draft Only).xlsx',
    ],
];
