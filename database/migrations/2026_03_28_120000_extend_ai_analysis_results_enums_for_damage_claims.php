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

        $this->extendAnalysisTypeEnum();
        $this->extendEntityTypeEnum();
    }

    public function down(): void
    {
        // Reverting would require restoring original enum values; leave as-is for safety
    }

    private function extendAnalysisTypeEnum(): void
    {
        $constraint = 'ai_analysis_results_analysis_type_check';
        $newValues = [
            'fraud_detection', 'predictive_maintenance', 'route_optimization',
            'driver_coaching', 'cost_optimization', 'compliance_prediction',
            'risk_assessment', 'fuel_efficiency', 'safety_scoring',
            'damage_detection', 'claims_processing',
        ];
        $this->replaceCheckConstraint('ai_analysis_results', 'analysis_type', $constraint, $newValues);
    }

    private function extendEntityTypeEnum(): void
    {
        $constraint = 'ai_analysis_results_entity_type_check';
        $newValues = [
            'vehicle', 'driver', 'trip', 'transaction', 'organization',
            'defect', 'incident', 'insurance_claim',
        ];
        $this->replaceCheckConstraint('ai_analysis_results', 'entity_type', $constraint, $newValues);
    }

    private function replaceCheckConstraint(string $table, string $column, string $constraintName, array $allowedValues): void
    {
        $constraint = DB::selectOne(
            "SELECT conname FROM pg_constraint WHERE conrelid = ?::regclass AND contype = 'c' AND pg_get_constraintdef(oid) LIKE ?",
            [$table, '%'.$column.'%']
        );
        $dropName = $constraint->conname ?? $constraintName;
        DB::statement("ALTER TABLE \"{$table}\" DROP CONSTRAINT IF EXISTS \"{$dropName}\"");
        $quoted = implode(', ', array_map(fn (string $v): string => "'".addslashes($v)."'", $allowedValues));
        DB::statement("ALTER TABLE \"{$table}\" ADD CONSTRAINT \"{$constraintName}\" CHECK (\"{$column}\"::text = ANY (ARRAY[{$quoted}]::text[]))");
    }
};
