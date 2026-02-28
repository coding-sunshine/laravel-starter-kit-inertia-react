<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ai_training_data', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();

            $table->enum('data_type', ['behavior_event', 'maintenance_record', 'fuel_transaction', 'trip_data']);
            $table->string('source_entity_type', 50);
            $table->unsignedBigInteger('source_entity_id');

            $table->string('training_label', 100)->nullable();
            $table->json('feature_vector');
            $table->json('ground_truth')->nullable();
            $table->decimal('data_quality_score', 5, 4)->nullable();

            $table->boolean('anonymized')->default(true);
            $table->boolean('consent_obtained')->default(false);
            $table->unsignedSmallInteger('retention_period_days')->default(2555);

            $table->boolean('used_for_training')->default(false);
            $table->json('model_names')->nullable();

            $table->timestamps();
            $table->timestamp('expires_at')->nullable();

            $table->index(['organization_id', 'data_type', 'used_for_training']);
            $table->index(['expires_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ai_training_data');
    }
};
