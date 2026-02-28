<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('report_executions', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignId('report_id')->constrained()->cascadeOnDelete();

            $table->timestamp('execution_start');
            $table->timestamp('execution_end')->nullable();

            $table->enum('status', ['running', 'completed', 'failed', 'cancelled'])->default('running');
            $table->enum('triggered_by', ['manual', 'scheduled', 'api']);
            $table->foreignId('triggered_by_user_id')->nullable()->constrained('users')->nullOnDelete();

            $table->json('parameters_used')->nullable();
            $table->json('filters_used')->nullable();

            $table->unsignedInteger('record_count')->nullable();
            $table->unsignedInteger('file_size_bytes')->nullable();
            $table->string('file_path', 500)->nullable();

            $table->text('error_message')->nullable();
            $table->unsignedInteger('execution_time_seconds')->nullable();

            $table->timestamps();

            $table->index(['report_id', 'execution_start']);
            $table->index(['organization_id', 'status', 'execution_start']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('report_executions');
    }
};
