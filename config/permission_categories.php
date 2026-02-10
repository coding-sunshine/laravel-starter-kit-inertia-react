<?php

declare(strict_types=1);

return [
    /*
    |--------------------------------------------------------------------------
    | Permission categories (wildcard grouping for role assignment)
    |--------------------------------------------------------------------------
    |
    | Categories group permissions by pattern so roles can be assigned
    | "users.*" instead of listing every permission. Used by
    | RolesAndPermissionsSeeder when permission_categories_enabled is true.
    |
    | Structure: 'category_key' => [
    |     'description' => 'Human description',
    |     'patterns'    => ['route.name.*', 'other.*'],  // wildcard patterns
    |     'exclude'     => ['route.name.specific'],     // optional
    |     'roles'       => ['admin', 'user'],           // roles that get this category
    | ]
    |
    */

    'categories' => [
        'admin_panel' => [
            'description' => 'Access admin panel and user management',
            'patterns' => [
                'access admin panel',
                'view users',
                'create users',
                'edit users',
                'delete users',
            ],
            'exclude' => [],
            'roles' => ['admin'],
        ],
        'filament_admin' => [
            'description' => 'Filament admin panel routes',
            'patterns' => ['filament.admin.*'],
            'exclude' => [],
            'roles' => ['admin'],
        ],
        'blog' => [
            'description' => 'Public blog (view, list)',
            'patterns' => ['blog.*'],
            'exclude' => [],
            'roles' => ['user', 'admin'],
        ],
        'changelog' => [
            'description' => 'Public changelog',
            'patterns' => ['changelog.*'],
            'exclude' => [],
            'roles' => ['user', 'admin'],
        ],
        'help' => [
            'description' => 'Help center (view, rate)',
            'patterns' => ['help.*'],
            'exclude' => [],
            'roles' => ['user', 'admin'],
        ],
    ],

    'roles' => [
        'super-admin' => [
            'strategy' => 'bypass',
            'explicit' => ['bypass-permissions'],
        ],
        'admin' => [
            'strategy' => 'categories',
            'categories' => ['admin_panel', 'filament_admin'],
            'explicit' => ['access admin panel', 'view users', 'create users', 'edit users', 'delete users'],
            'exclude' => [],
        ],
        'user' => [
            'strategy' => 'explicit',
            'explicit' => [],
            'exclude' => [],
        ],
    ],
];
