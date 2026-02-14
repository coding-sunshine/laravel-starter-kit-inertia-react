<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $tableNames = config('permission.table_names');
        $teamKey = config('permission.column_names.team_foreign_key');

        DB::table($tableNames['roles'])
            ->whereNull($teamKey)
            ->update([$teamKey => 0]);
    }

    public function down(): void
    {
        $tableNames = config('permission.table_names');
        $teamKey = config('permission.column_names.team_foreign_key');

        DB::table($tableNames['roles'])
            ->where($teamKey, 0)
            ->update([$teamKey => null]);
    }
};
