<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $driver = DB::connection()->getDriverName();
        if ($driver === 'pgsql') {
            $constraintRow = DB::selectOne(
                "SELECT conname FROM pg_constraint WHERE conrelid = ?::regclass AND contype = 'c' AND pg_get_constraintdef(oid) LIKE ?",
                ['ai_job_runs', '%job_type%']
            );
            $dropName = $constraintRow->conname ?? 'ai_job_runs_job_type_check';
            DB::statement("ALTER TABLE ai_job_runs DROP CONSTRAINT IF EXISTS \"{$dropName}\"");
            $values = ['fraud_detection', 'maintenance_prediction', 'behavior_analysis', 'route_optimization', 'cost_analysis', 'compliance_check', 'risk_assessment', 'model_training', 'data_processing', 'compliance_prediction'];
            $quoted = implode(', ', array_map(fn (string $v): string => "'".addslashes($v)."'", $values));
            DB::statement("ALTER TABLE ai_job_runs ADD CONSTRAINT ai_job_runs_job_type_check CHECK (job_type::text = ANY (ARRAY[{$quoted}]::text[]))");

            return;
        }
        if ($driver === 'mysql') {
            DB::statement("ALTER TABLE ai_job_runs MODIFY COLUMN job_type ENUM('fraud_detection', 'maintenance_prediction', 'behavior_analysis', 'route_optimization', 'cost_analysis', 'compliance_check', 'risk_assessment', 'model_training', 'data_processing', 'compliance_prediction') NOT NULL");
        }
    }

    public function down(): void
    {
        $driver = DB::connection()->getDriverName();
        if ($driver === 'pgsql') {
            DB::statement('ALTER TABLE ai_job_runs DROP CONSTRAINT IF EXISTS ai_job_runs_job_type_check');
            $values = ['fraud_detection', 'maintenance_prediction', 'behavior_analysis', 'route_optimization', 'cost_analysis', 'compliance_check', 'risk_assessment', 'model_training', 'data_processing'];
            $quoted = implode(', ', array_map(fn (string $v): string => "'".addslashes($v)."'", $values));
            DB::statement("ALTER TABLE ai_job_runs ADD CONSTRAINT ai_job_runs_job_type_check CHECK (job_type::text = ANY (ARRAY[{$quoted}]::text[]))");
        }
        if ($driver === 'mysql') {
            DB::statement("ALTER TABLE ai_job_runs MODIFY COLUMN job_type ENUM('fraud_detection', 'maintenance_prediction', 'behavior_analysis', 'route_optimization', 'cost_analysis', 'compliance_check', 'risk_assessment', 'model_training', 'data_processing') NOT NULL");
        }
    }
};
