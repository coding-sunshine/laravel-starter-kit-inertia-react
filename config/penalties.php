<?php

declare(strict_types=1);

return [
    /*
    |--------------------------------------------------------------------------
    | Penalty Rate Constants
    |--------------------------------------------------------------------------
    |
    | Single source of truth for all penalty-rate values used by the
    | Manager Brief signal collectors and any other penalty calculations.
    | Override via environment variables for environment-specific tuning.
    |
    */

    'overload' => [
        /**
         * Penalty charged per metric-tonne of overloaded coal (Rs/MT).
         */
        'rs_per_mt' => (float) env('PENALTY_OVERLOAD_RS_PER_MT', 1000),
    ],

    'demurrage' => [
        /**
         * Demurrage charge per hour of detention beyond the free period (Rs/hour).
         */
        'rs_per_hour' => (float) env('PENALTY_DEMURRAGE_RS_PER_HOUR', 5000),
    ],

    'underload' => [
        /**
         * Penalty charged per metric-tonne of underloaded coal (Rs/MT).
         */
        'rs_per_mt' => (float) env('PENALTY_UNDERLOAD_RS_PER_MT', 500),
    ],

    'sla' => [
        /**
         * Maximum allowed hours from placement to rake dispatch before
         * demurrage penalties begin accruing.
         */
        'placement_to_dispatch_hours' => (int) env('PENALTY_SLA_HOURS', 12),
    ],
];
