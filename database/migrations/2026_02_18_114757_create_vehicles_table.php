<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('vehicles', function (Blueprint $table): void {
            $table->id();
            $table->string('vehicle_number', 20)->unique();
            $table->string('rfid_tag', 50)->nullable()->unique();
            $table->decimal('permitted_capacity_mt', 10, 2);
            $table->decimal('tare_weight_mt', 10, 2);
            $table->string('owner_name');
            $table->string('vehicle_type'); // e.g., "Tipper", "Trailer", "Truck"
            $table->string('gps_device_id', 50)->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();

            $table->index('vehicle_number');
            $table->index('is_active');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('vehicles');
    }
};
