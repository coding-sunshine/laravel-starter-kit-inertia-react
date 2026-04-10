<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Remove all genealabs/laravel-governor database artifacts.
 *
 * The package is abandoned and was only used for a single ownership check
 * on `announcements.governor_owned_by`, which is now replaced by `created_by`.
 * This migration:
 *   1. Drops the `governor_owned_by` column from `announcements`
 *   2. Drops all 11 `governor_*` tables created by the package
 */
return new class extends Migration
{
    /**
     * Drop order matters: tables with foreign keys must be dropped before
     * the tables they reference.
     *
     * @var list<string>
     */
    private array $governorTables = [
        'governor_team_invitations',   // FK → governor_teams, governor_roles
        'governor_teamables',          // FK → governor_teams
        'governor_team_user',          // FK → governor_teams, users
        'governor_permissions',        // FK → governor_teams, governor_actions, governor_entities, governor_roles
        'governor_ownerships',         // FK → governor_entities
        'governor_role_user',          // FK → governor_roles, users
        'governor_teams',              // FK → users
        'governor_entities',
        'governor_actions',
        'governor_groups',
        'governor_roles',
    ];

    public function up(): void
    {
        if (Schema::hasTable('announcements') && Schema::hasColumn('announcements', 'governor_owned_by')) {
            Schema::table('announcements', function (Blueprint $table): void {
                // Drop foreign key before dropping the column (if it exists).
                try {
                    $table->dropForeign(['governor_owned_by']);
                } catch (\Throwable) {
                    // Foreign key may not exist on this driver; ignore.
                }
                $table->dropColumn('governor_owned_by');
            });
        }

        // Drop stray foreign key from users.current_team_id → governor_teams.
        // Laravel's dropForeign() can miss constraints whose names diverge from
        // the default convention, so fall back to a raw statement on Postgres/MySQL.
        if (Schema::hasTable('users') && Schema::hasColumn('users', 'current_team_id')) {
            try {
                $driver = DB::connection()->getDriverName();
                if ($driver === 'pgsql') {
                    DB::statement('ALTER TABLE users DROP CONSTRAINT IF EXISTS users_current_team_id_foreign');
                } elseif ($driver === 'mysql' || $driver === 'mariadb') {
                    DB::statement('ALTER TABLE `users` DROP FOREIGN KEY `users_current_team_id_foreign`');
                }
            } catch (\Throwable) {
                // Constraint may not exist; ignore.
            }
        }

        foreach ($this->governorTables as $table) {
            Schema::dropIfExists($table);
        }
    }

    public function down(): void
    {
        // Intentionally no-op. The governor package is abandoned; recreating
        // its schema here would require re-adding the dependency. If you need
        // to roll this back, restore the old `create_governor_*` migrations
        // from git history and re-install the package.
    }
};
