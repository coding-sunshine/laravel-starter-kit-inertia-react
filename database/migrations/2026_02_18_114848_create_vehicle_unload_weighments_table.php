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
        Schema::create('vehicle_unload_weighments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('vehicle_unload_id')
                ->constrained('vehicle_unloads')
                ->onDelete('cascade');

            $table->decimal('gross_weight_mt', 8, 2);
            $table->decimal('tare_weight_mt', 8, 2)->nullable();
            $table->decimal('net_weight_mt', 8, 2);

            // Status
            $table->string('weighment_type', 30); // Tare / gross
            $table->string('weighment_status', 30); // PASS / FAIL

            // Source Information
            $table->string('data_source', 30)->default('MANUAL'); // MANUAL / API

            // For API weighbridge transaction id
            $table->string('external_reference')->nullable();

            $table->json('raw_payload')->nullable();
            $table->dateTime('weighment_time');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('vehicle_unload_weighments');
    }
};
