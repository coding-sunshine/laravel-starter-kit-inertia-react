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
        Schema::create('powerplant_siding_distances', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('power_plant_id')->constrained()->onDelete('cascade');
            $table->foreignId('siding_id')->constrained()->onDelete('cascade');
            $table->decimal('distance_km', 8, 2); // distance in kilometers
            $table->timestamps();

            $table->unique(['power_plant_id', 'siding_id']);
            $table->index(['power_plant_id', 'siding_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('powerplant_siding_distances');
    }
};
