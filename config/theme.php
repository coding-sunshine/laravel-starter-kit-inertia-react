<?php

declare(strict_types=1);

return [
    /*
    |--------------------------------------------------------------------------
    | Theme presets (single source for ThemeSettings, Filament, org branding)
    |--------------------------------------------------------------------------
    */
    'preset' => env('THEME_PRESET', 'fleet'),

    'base_color' => env('THEME_BASE_COLOR', 'neutral'),

    'radius' => env('THEME_RADIUS', 'default'),

    'font' => env('THEME_FONT', 'inter'),

    'default_appearance' => env('THEME_DEFAULT_APPEARANCE', 'light'), // light | dark | system

    /*
    |--------------------------------------------------------------------------
    | Preset list (keys used in ThemeSettings and org branding)
    |--------------------------------------------------------------------------
    */
    'presets' => [
        'default' => [
            'label' => 'Default',
        ],
        'fleet' => [
            'label' => 'Fleet',
        ],
        'vega' => [
            'label' => 'Vega',
        ],
        'nova' => [
            'label' => 'Nova',
        ],
        'metronic' => [
            'label' => 'Metronic',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Subset of presets orgs can choose (org branding)
    |--------------------------------------------------------------------------
    */
    'org_allowed_presets' => ['default', 'fleet', 'vega', 'nova', 'metronic'],

    'base_colors' => [
        'neutral' => 'Neutral',
        'slate' => 'Slate',
        'gray' => 'Gray',
        'zinc' => 'Zinc',
        'stone' => 'Stone',
    ],

    'radii' => [
        'none' => 'None',
        'sm' => 'Small',
        'default' => 'Default',
        'md' => 'Medium',
        'lg' => 'Large',
        'full' => 'Full',
    ],

    'fonts' => [
        'inter' => 'Inter',
        'instrument-sans' => 'Instrument Sans',
        'geist-sans' => 'Geist Sans',
    ],

    'appearances' => [
        'light' => 'Light',
        'dark' => 'Dark',
        'system' => 'System',
    ],
];
