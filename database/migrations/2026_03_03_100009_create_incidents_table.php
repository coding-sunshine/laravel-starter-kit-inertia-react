<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('incidents', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignId('vehicle_id')->constrained();
            $table->foreignId('driver_id')->nullable()->constrained()->nullOnDelete();

            $table->string('incident_number', 50);
            $table->date('incident_date');
            $table->time('incident_time');
            $table->timestamp('incident_timestamp');

            $table->enum('incident_type', ['collision', 'theft', 'vandalism', 'fire', 'flood', 'breakdown', 'other']);
            $table->enum('severity', ['minor', 'major', 'total_loss']);

            $table->text('location_description')->nullable();
            $table->decimal('lat', 9, 6)->nullable();
            $table->decimal('lng', 9, 6)->nullable();

            $table->string('weather_conditions', 100)->nullable();
            $table->string('road_conditions', 100)->nullable();
            $table->string('traffic_conditions', 100)->nullable();
            $table->enum('fault_determination', ['our_fault', 'third_party_fault', 'no_fault', 'disputed', 'unknown'])->default('unknown');

            $table->boolean('police_attended')->default(false);
            $table->string('police_reference', 100)->nullable();
            $table->boolean('injuries_reported')->default(false);
            $table->unsignedTinyInteger('injury_count')->default(0);

            $table->boolean('third_party_involved')->default(false);
            $table->json('third_party_details')->nullable();
            $table->json('witnesses')->nullable();

            $table->text('description');
            $table->text('initial_assessment')->nullable();

            $table->decimal('estimated_damage_cost', 12, 2)->nullable();
            $table->decimal('actual_repair_cost', 12, 2)->nullable();
            $table->boolean('vehicle_driveable')->default(true);
            $table->boolean('recovery_required')->default(false);
            $table->decimal('recovery_cost', 8, 2)->nullable();

            $table->enum('status', ['reported', 'investigating', 'repair_approved', 'repairing', 'settled', 'closed'])->default('reported');
            $table->foreignId('reported_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('investigating_officer')->nullable()->constrained('users')->nullOnDelete();

            $table->unsignedTinyInteger('photos_count')->default(0);
            $table->unsignedTinyInteger('documents_count')->default(0);

            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();
            $table->foreignId('deleted_by')->nullable()->constrained('users')->nullOnDelete();

            $table->unique(['organization_id', 'incident_number']);
            $table->index(['vehicle_id', 'incident_timestamp']);
            $table->index(['driver_id', 'incident_timestamp']);
            $table->index(['incident_date', 'status']);
            $table->index(['fault_determination', 'severity']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('incidents');
    }
};
