<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('geofences', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignId('location_id')->nullable()->constrained('locations')->nullOnDelete();

            $table->string('name', 200);
            $table->text('description')->nullable();
            $table->string('geofence_type', 50); // circle, polygon, administrative_boundary

            $table->decimal('center_lat', 10, 8)->nullable();
            $table->decimal('center_lng', 11, 8)->nullable();
            $table->unsignedInteger('radius_meters')->nullable();
            $table->json('polygon_coordinates')->nullable();

            $table->string('location_type', 50)->nullable(); // depot, customer_site, service_area, restricted_zone, etc.

            $table->boolean('alert_on_entry')->default(false);
            $table->boolean('alert_on_exit')->default(false);
            $table->boolean('alert_on_speeding')->default(false);
            $table->unsignedSmallInteger('speed_limit_kmh')->nullable();

            $table->boolean('time_restrictions_apply')->default(false);
            $table->time('allowed_hours_start')->nullable();
            $table->time('allowed_hours_end')->nullable();
            $table->json('allowed_days')->nullable();

            $table->boolean('is_active')->default(true);
            $table->date('monitoring_start_date')->nullable();
            $table->date('monitoring_end_date')->nullable();

            $table->unsignedInteger('total_entries')->default(0);
            $table->unsignedInteger('total_exits')->default(0);
            $table->unsignedInteger('total_violations')->default(0);
            $table->timestamp('last_activity_at')->nullable();

            $table->text('notes')->nullable();

            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();
            $table->foreignId('deleted_by')->nullable()->constrained('users')->nullOnDelete();

            $table->index(['organization_id', 'is_active']);
            $table->index(['location_id']);
            $table->index(['center_lat', 'center_lng', 'radius_meters']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('geofences');
    }
};
