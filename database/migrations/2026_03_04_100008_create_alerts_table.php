<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('alerts', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();

            $table->enum('alert_type', [
                'compliance_expiry', 'maintenance_due', 'defect_reported',
                'incident_reported', 'behavior_violation', 'fuel_anomaly',
                'cost_threshold', 'geofence_violation', 'speed_violation',
                'working_time_violation', 'system_error',
            ]);
            $table->enum('severity', ['info', 'warning', 'critical', 'emergency']);

            $table->string('title', 200);
            $table->text('description');

            $table->string('entity_type', 50)->nullable();
            $table->unsignedBigInteger('entity_id')->nullable();

            $table->timestamp('triggered_at');
            $table->timestamp('acknowledged_at')->nullable();
            $table->foreignId('acknowledged_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('resolved_at')->nullable();
            $table->foreignId('resolved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->text('resolution_notes')->nullable();

            $table->enum('status', ['active', 'acknowledged', 'resolved', 'dismissed'])->default('active');
            $table->boolean('notification_sent')->default(false);

            $table->unsignedTinyInteger('escalation_level')->default(0);
            $table->timestamp('escalated_at')->nullable();
            $table->timestamp('auto_resolve_at')->nullable();
            $table->json('metadata')->nullable();

            $table->timestamps();

            $table->index(['organization_id', 'status', 'severity', 'triggered_at']);
            $table->index(['entity_type', 'entity_id', 'status']);
            $table->index(['escalation_level', 'escalated_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('alerts');
    }
};
