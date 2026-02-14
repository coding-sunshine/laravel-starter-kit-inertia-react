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
        $guard = 'web';

        foreach (DB::table('organizations')->get() as $org) {
            $adminRoleId = DB::table($tableNames['roles'])->insertGetId([
                'name' => 'admin',
                'guard_name' => $guard,
                $teamKey => $org->id,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
            $memberRoleId = DB::table($tableNames['roles'])->insertGetId([
                'name' => 'member',
                'guard_name' => $guard,
                $teamKey => $org->id,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            foreach (DB::table('organization_user')->where('organization_id', $org->id)->get() as $row) {
                $roleId = ($org->owner_id && (int) $row->user_id === (int) $org->owner_id)
                    ? $adminRoleId
                    : $memberRoleId;
                DB::table($tableNames['model_has_roles'])->insert([
                    'role_id' => $roleId,
                    'model_type' => App\Models\User::class,
                    'model_id' => $row->user_id,
                    $teamKey => $org->id,
                ]);
            }
        }
    }

    public function down(): void
    {
        $tableNames = config('permission.table_names');
        $teamKey = config('permission.column_names.team_foreign_key');

        $orgIds = DB::table('organizations')->pluck('id');
        DB::table($tableNames['model_has_roles'])->whereIn($teamKey, $orgIds)->delete();
        DB::table($tableNames['roles'])->whereIn($teamKey, $orgIds)->delete();
    }
};
