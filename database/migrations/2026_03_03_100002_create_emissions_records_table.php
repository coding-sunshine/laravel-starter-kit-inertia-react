<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('emissions_records', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignId('vehicle_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('driver_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('trip_id')->nullable()->constrained()->nullOnDelete();

            $table->enum('scope', ['vehicle', 'trip', 'driver', 'organization'])->default('vehicle');
            $table->enum('emissions_type', ['fuel_combustion', 'electricity', 'well_to_tank', 'total'])->default('fuel_combustion');
            $table->date('record_date');
            $table->decimal('co2_kg', 12, 3)->default(0);
            $table->decimal('fuel_consumed_litres', 10, 3)->nullable();
            $table->decimal('distance_km', 10, 2)->nullable();
            $table->json('metadata')->nullable();

            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['organization_id', 'record_date']);
            $table->index(['vehicle_id', 'record_date']);
            $table->index(['trip_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('emissions_records');
    }
};
