<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('trips', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();

            $table->foreignId('vehicle_id')->constrained('vehicles')->cascadeOnDelete();
            $table->foreignId('driver_id')->nullable()->constrained('drivers')->nullOnDelete();
            $table->foreignId('route_id')->nullable()->constrained('routes')->nullOnDelete();

            $table->foreignId('start_location_id')->nullable()->constrained('locations')->nullOnDelete();
            $table->foreignId('end_location_id')->nullable()->constrained('locations')->nullOnDelete();

            $table->dateTime('planned_start_time')->nullable();
            $table->dateTime('planned_end_time')->nullable();
            $table->dateTime('started_at')->nullable();
            $table->dateTime('ended_at')->nullable();

            $table->string('status', 50)->index();

            $table->decimal('distance_km', 10, 2)->nullable();
            $table->unsignedInteger('duration_minutes')->nullable();

            $table->text('notes')->nullable();

            $table->timestamps();

            $table->index(['organization_id', 'vehicle_id', 'started_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('trips');
    }
};
