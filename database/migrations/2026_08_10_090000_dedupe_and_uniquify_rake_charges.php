<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * `RakeCharge` rows are written with updateOrCreate keyed on
 * (rake_id, diverrt_destination_id, charge_type, is_actual_charges) but nothing
 * enforced that key, so concurrent RR imports created duplicate FREIGHT / GST /
 * OTHER_CHARGE rows that were then summed twice in the charge reports.
 *
 * Collapse existing duplicates onto the oldest row (they carry identical
 * amounts) and add the missing unique index.
 */
return new class extends Migration
{
    private const CHILD_TABLES = ['rr_charges', 'rr_penalty_snapshots', 'applied_penalties'];

    public function up(): void
    {
        $this->collapseDuplicates();

        // Expression index because diverrt_destination_id is nullable and
        // Postgres treats NULLs as distinct, which is what allowed the
        // duplicates. Other drivers (SQLite in tests) get no index.
        if (DB::getDriverName() === 'pgsql') {
            DB::statement('CREATE UNIQUE INDEX rake_charges_scope_unique ON rake_charges (rake_id, COALESCE(diverrt_destination_id, 0), charge_type, is_actual_charges)');
        }
    }

    public function down(): void
    {
        if (DB::getDriverName() === 'pgsql') {
            DB::statement('DROP INDEX IF EXISTS rake_charges_scope_unique');
        }
    }

    private function collapseDuplicates(): void
    {
        $groups = DB::table('rake_charges')
            ->select('rake_id', 'diverrt_destination_id', 'charge_type', 'is_actual_charges')
            ->selectRaw('MIN(id) as keep_id, COUNT(*) as row_count')
            ->groupBy('rake_id', 'diverrt_destination_id', 'charge_type', 'is_actual_charges')
            ->havingRaw('COUNT(*) > 1')
            ->get();

        foreach ($groups as $group) {
            $duplicateIds = DB::table('rake_charges')
                ->where('rake_id', $group->rake_id)
                ->where('charge_type', $group->charge_type)
                ->where('is_actual_charges', $group->is_actual_charges)
                ->when(
                    $group->diverrt_destination_id === null,
                    fn ($query) => $query->whereNull('diverrt_destination_id'),
                    fn ($query) => $query->where('diverrt_destination_id', $group->diverrt_destination_id),
                )
                ->where('id', '!=', $group->keep_id)
                ->pluck('id')
                ->all();

            if ($duplicateIds === []) {
                continue;
            }

            foreach (self::CHILD_TABLES as $table) {
                DB::table($table)
                    ->whereIn('rake_charge_id', $duplicateIds)
                    ->update(['rake_charge_id' => $group->keep_id]);
            }

            DB::table('rake_charges')->whereIn('id', $duplicateIds)->delete();
        }
    }
};
