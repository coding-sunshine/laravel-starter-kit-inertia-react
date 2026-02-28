<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ai_job_runs', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();

            $table->enum('job_type', [
                'fraud_detection', 'maintenance_prediction', 'behavior_analysis',
                'route_optimization', 'cost_analysis', 'compliance_check',
                'risk_assessment', 'model_training', 'data_processing',
            ]);
            $table->string('entity_type', 20)->nullable();
            $table->json('entity_ids')->nullable();
            $table->json('parameters')->nullable();

            $table->enum('status', ['queued', 'processing', 'completed', 'failed', 'cancelled'])->default('queued');
            $table->unsignedTinyInteger('priority')->default(50);

            $table->timestamp('scheduled_at')->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->string('worker_id', 100)->nullable();
            $table->unsignedTinyInteger('progress_percentage')->default(0);
            $table->timestamp('estimated_completion')->nullable();

            $table->unsignedInteger('cpu_time_seconds')->nullable();
            $table->unsignedInteger('memory_usage_mb')->nullable();

            $table->unsignedTinyInteger('retry_count')->default(0);
            $table->unsignedTinyInteger('max_retries')->default(3);
            $table->text('error_message')->nullable();
            $table->string('error_code', 50)->nullable();
            $table->json('result_data')->nullable();

            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['status', 'priority', 'created_at']);
            $table->index(['organization_id', 'job_type', 'created_at']);
            $table->index(['worker_id', 'status']);
            $table->index(['scheduled_at', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ai_job_runs');
    }
};
