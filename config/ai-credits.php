<?php

declare(strict_types=1);

return [

    /*
    |--------------------------------------------------------------------------
    | Default Credits Per Period
    |--------------------------------------------------------------------------
    |
    | Default number of AI credits allocated to each organization per billing
    | period. Overridden by plan.ai_credits_per_period if set.
    |
    */
    'default_credits_per_period' => (int) env('AI_CREDITS_DEFAULT', 100),

    /*
    |--------------------------------------------------------------------------
    | Credit Costs Per Action
    |--------------------------------------------------------------------------
    |
    | Credits consumed per AI interaction type. Adjustable by superadmin
    | via Filament → Settings → AI Credits.
    |
    */
    'costs' => [
        'chat_message' => 1,
        'nlq_query' => 1,
        'ai_insights' => 2,
        'ai_suggest' => 1,
        'ai_column_summary' => 1,
        'ai_enrich' => 2,
        'ai_visualize' => 2,
        'email_draft' => 2,
        'lead_score' => 1,
        'property_match' => 1,
        'ai_summary' => 1,
        'dashboard_insight' => 2,
        'bulk_enrich_per_10' => 5,
    ],

];
