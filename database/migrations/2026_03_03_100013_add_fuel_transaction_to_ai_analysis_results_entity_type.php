<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        if (DB::getDriverName() !== 'pgsql') {
            return;
        }

        $constraint = 'ai_analysis_results_entity_type_check';
        $newValues = [
            'vehicle', 'driver', 'trip', 'transaction', 'organization',
            'defect', 'incident', 'insurance_claim', 'fuel_transaction',
        ];
        $constraintRow = DB::selectOne(
            "SELECT conname FROM pg_constraint WHERE conrelid = ?::regclass AND contype = 'c' AND pg_get_constraintdef(oid) LIKE ?",
            ['ai_analysis_results', '%entity_type%']
        );
        $dropName = $constraintRow->conname ?? $constraint;
        DB::statement("ALTER TABLE ai_analysis_results DROP CONSTRAINT IF EXISTS \"{$dropName}\"");
        $quoted = implode(', ', array_map(fn (string $v): string => "'".addslashes($v)."'", $newValues));
        DB::statement("ALTER TABLE ai_analysis_results ADD CONSTRAINT \"{$constraint}\" CHECK (entity_type::text = ANY (ARRAY[{$quoted}]::text[]))");
    }

    public function down(): void
    {
        // Revert would require restoring previous enum; leave as-is
    }
};
