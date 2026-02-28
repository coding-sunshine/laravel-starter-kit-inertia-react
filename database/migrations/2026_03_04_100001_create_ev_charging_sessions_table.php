<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ev_charging_sessions', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignId('vehicle_id')->constrained();
            $table->foreignId('driver_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('charging_station_id')->constrained('ev_charging_stations');

            $table->string('connector_id', 50)->nullable();
            $table->string('session_id', 100)->unique();

            $table->timestamp('start_timestamp');
            $table->timestamp('end_timestamp')->nullable();
            $table->unsignedInteger('duration_minutes')->nullable();

            $table->decimal('energy_delivered_kwh', 8, 3)->nullable();
            $table->decimal('charging_rate_kw', 6, 2)->nullable();
            $table->unsignedTinyInteger('initial_soc_percent')->nullable();
            $table->unsignedTinyInteger('final_soc_percent')->nullable();

            $table->decimal('cost', 8, 2)->nullable();
            $table->decimal('cost_per_kwh', 6, 4)->nullable();
            $table->string('payment_method', 50)->nullable();

            $table->enum('session_type', ['ac_slow', 'ac_fast', 'dc_rapid', 'dc_ultra_rapid']);
            $table->boolean('interrupted')->default(false);
            $table->string('interruption_reason', 200)->nullable();

            $table->timestamps();

            $table->index(['vehicle_id', 'start_timestamp']);
            $table->index(['charging_station_id', 'start_timestamp']);
            $table->index(['organization_id', 'start_timestamp']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ev_charging_sessions');
    }
};
