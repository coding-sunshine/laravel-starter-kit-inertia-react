<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ev_battery_data', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('vehicle_id')->constrained()->cascadeOnDelete();

            $table->timestamp('recorded_at');

            $table->unsignedTinyInteger('soc_percent');
            $table->unsignedTinyInteger('soh_percent')->nullable();
            $table->decimal('voltage', 6, 2)->nullable();
            $table->decimal('current_amps', 8, 2)->nullable();
            $table->tinyInteger('temperature_celsius')->nullable();

            $table->unsignedSmallInteger('range_remaining_km')->nullable();
            $table->decimal('energy_consumed_kwh', 8, 3)->nullable();
            $table->decimal('regenerative_energy_kwh', 8, 3)->nullable();

            $table->enum('charging_status', ['not_charging', 'charging', 'fast_charging', 'complete'])->default('not_charging');
            $table->json('battery_warnings')->nullable();

            $table->timestamps();

            $table->index(['vehicle_id', 'recorded_at']);
            $table->index(['soc_percent', 'recorded_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ev_battery_data');
    }
};
