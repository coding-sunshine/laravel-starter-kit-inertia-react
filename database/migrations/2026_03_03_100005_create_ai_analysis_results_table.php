<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ai_analysis_results', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();

            $table->enum('analysis_type', [
                'fraud_detection', 'predictive_maintenance', 'route_optimization',
                'driver_coaching', 'cost_optimization', 'compliance_prediction',
                'risk_assessment', 'fuel_efficiency', 'safety_scoring'
            ]);
            $table->enum('entity_type', ['vehicle', 'driver', 'trip', 'transaction', 'organization']);
            $table->unsignedBigInteger('entity_id');

            $table->string('model_name', 100);
            $table->string('model_version', 50)->nullable();
            $table->decimal('confidence_score', 5, 4);
            $table->decimal('risk_score', 5, 2)->default(0);
            $table->enum('priority', ['low', 'medium', 'high', 'critical']);

            $table->string('primary_finding', 500);
            $table->json('detailed_analysis');
            $table->json('recommendations')->nullable();
            $table->json('action_items')->nullable();
            $table->json('business_impact')->nullable();

            $table->enum('status', ['pending', 'reviewed', 'actioned', 'dismissed', 'escalated'])->default('pending');
            $table->timestamp('reviewed_at')->nullable();
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->text('review_notes')->nullable();

            $table->timestamp('expires_at')->nullable();
            $table->foreignId('superseded_by')->nullable()->constrained('ai_analysis_results')->nullOnDelete();
            $table->boolean('training_feedback')->nullable();
            $table->unsignedTinyInteger('feedback_rating')->nullable();

            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();
            $table->foreignId('deleted_by')->nullable()->constrained('users')->nullOnDelete();

            $table->index(['organization_id', 'status', 'priority', 'created_at']);
            $table->index(['entity_type', 'entity_id', 'analysis_type']);
            $table->index(['model_name', 'confidence_score', 'created_at']);
            $table->index(['expires_at', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ai_analysis_results');
    }
};
