<?php

declare(strict_types=1);

return [

    /*
    |--------------------------------------------------------------------------
    | Default Payment Gateway
    |--------------------------------------------------------------------------
    |
    | Supported: "stripe", "paddle", "manual"
    |
    */
    'default_gateway' => env('DEFAULT_PAYMENT_GATEWAY', 'stripe'),

    /*
    |--------------------------------------------------------------------------
    | Currency
    |--------------------------------------------------------------------------
    */
    'currency' => env('BILLING_CURRENCY', 'usd'),

    /*
    |--------------------------------------------------------------------------
    | Trial Days
    |--------------------------------------------------------------------------
    */
    'trial_days' => (int) env('BILLING_TRIAL_DAYS', 14),

    /*
    |--------------------------------------------------------------------------
    | Credit Expiration (days)
    |--------------------------------------------------------------------------
    */
    'credit_expiration_days' => (int) env('BILLING_CREDIT_EXPIRATION_DAYS', 365),

    /*
    |--------------------------------------------------------------------------
    | Dunning Reminder Intervals (days after failure)
    |--------------------------------------------------------------------------
    */
    'dunning_intervals' => [
        3,
        7,
        14,
    ],

    /*
    |--------------------------------------------------------------------------
    | Lemon Squeezy: Cents per Credit (fallback when custom_data.credits not set)
    |--------------------------------------------------------------------------
    | Used by AddCreditsFromLemonSqueezyOrder when deriving credits from order total.
    | Set to 0 to disable fallback (requires custom_data.credits in checkout).
    */
    'lemon_squeezy_cents_per_credit' => (int) env('BILLING_LEMON_SQUEEZY_CENTS_PER_CREDIT', 10),

    /*
    |--------------------------------------------------------------------------
    | Geo-restriction (laravel-geo-genius)
    |--------------------------------------------------------------------------
    */
    'geo_restriction_enabled' => (bool) env('BILLING_GEO_RESTRICTION_ENABLED', false),
    'geo_blocked_countries' => array_map(strtoupper(...), array_filter(explode(',', (string) env('BILLING_GEO_BLOCKED_COUNTRIES', '')))),
    'geo_allowed_countries' => array_map(strtoupper(...), array_filter(explode(',', (string) env('BILLING_GEO_ALLOWED_COUNTRIES', '')))),

];
