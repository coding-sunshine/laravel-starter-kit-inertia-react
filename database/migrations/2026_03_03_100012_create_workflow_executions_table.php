<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('workflow_executions', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('workflow_definition_id')->constrained()->cascadeOnDelete();

            $table->timestamp('started_at');
            $table->timestamp('completed_at')->nullable();
            $table->string('trigger_event', 100)->nullable();
            $table->string('trigger_entity_type', 50)->nullable();
            $table->unsignedBigInteger('trigger_entity_id')->nullable();
            $table->json('trigger_data')->nullable();

            $table->enum('status', ['pending', 'running', 'completed', 'failed', 'cancelled'])->default('pending');
            $table->unsignedSmallInteger('steps_attempted')->default(0);
            $table->unsignedSmallInteger('steps_completed')->default(0);
            $table->unsignedSmallInteger('steps_failed')->default(0);

            $table->text('error_message')->nullable();
            $table->json('error_details')->nullable();
            $table->json('result_data')->nullable();

            $table->timestamps();

            $table->index(['workflow_definition_id', 'started_at']);
            $table->index(['status', 'started_at']);
            $table->index(['trigger_entity_type', 'trigger_entity_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('workflow_executions');
    }
};
