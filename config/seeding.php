<?php

declare(strict_types=1);

return [
    /*
    |--------------------------------------------------------------------------
    | Development seeders whitelist
    |--------------------------------------------------------------------------
    |
    | When the Development category runs, only these seeders are executed.
    | Set SEED_DEVELOPMENT_WHITELIST in .env (comma-separated full class names)
    | to override. Use empty value to run no Development seeders.
    |
    */

    'development_whitelist' => (function (): array {
        $fromEnv = env('SEED_DEVELOPMENT_WHITELIST');
        if ($fromEnv === null) {
            return [
                Database\Seeders\Development\UsersSeeder::class,
                Database\Seeders\Development\RealDataImportSeeder::class,
            ];
        }
        $list = array_filter(array_map('trim', explode(',', (string) $fromEnv)), fn (string $s): bool => $s !== '');

        return array_values($list);
    })(),

    /*
    |--------------------------------------------------------------------------
    | Auto-Generate JSON
    |--------------------------------------------------------------------------
    |
    | When true, automatically generates JSON seed data when missing or empty.
    | Uses AI if available, otherwise falls back to Faker.
    |
    */

    'auto_generate_json' => env('SEEDING_AUTO_GENERATE_JSON', true),

    /*
    |--------------------------------------------------------------------------
    | Auto-Sync After Migrations
    |--------------------------------------------------------------------------
    |
    | When true, automatically syncs seed specs after migrations complete.
    | This ensures specs stay in sync with schema changes.
    |
    */

    'auto_sync_after_migrations' => env('SEEDING_AUTO_SYNC_AFTER_MIGRATIONS', true),

    /*
    |--------------------------------------------------------------------------
    | Pre-Commit Auto-Fix
    |--------------------------------------------------------------------------
    |
    | Controls pre-commit hook behavior:
    | - false: Block commit and show error (default)
    | - 'prompt': Show interactive prompt to auto-generate (default)
    | - true: Auto-generate without prompting (use with caution)
    |
    */

    'pre_commit_auto_fix' => env('SEEDING_PRE_COMMIT_AUTO_FIX', 'prompt'),

    /*
    |--------------------------------------------------------------------------
    | AI Fallback Enabled
    |--------------------------------------------------------------------------
    |
    | When true, falls back to traditional Faker generation when AI is
    | unavailable. When false, commands will fail if AI is not configured.
    |
    */

    'ai_fallback_enabled' => env('SEEDING_AI_FALLBACK_ENABLED', true),

    /*
    |--------------------------------------------------------------------------
    | Default JSON Generation Count
    |--------------------------------------------------------------------------
    |
    | Default number of records to generate when auto-generating JSON.
    |
    */

    'default_json_count' => env('SEEDING_DEFAULT_JSON_COUNT', 5),

    /*
    |--------------------------------------------------------------------------
    | Auto-Regenerate Seeders
    |--------------------------------------------------------------------------
    |
    | When true, automatically regenerates seeders when relationships change
    | after migrations. This ensures seeders stay in sync with model changes.
    |
    */

    'auto_regenerate_seeders' => env('SEEDING_AUTO_REGENERATE_SEEDERS', true),
];
