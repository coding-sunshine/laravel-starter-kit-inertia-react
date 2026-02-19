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
        Schema::create('vehicle_arrivals', function (Blueprint $table) {
            $table->id();
            $table->foreignId('siding_id')->constrained();
            $table->foreignId('vehicle_id')->constrained();
            $table->foreignId('indent_id')->nullable()->constrained();

            // Status: pending, unloading, unloaded, completed, cancelled
            $table->enum('status', ['pending', 'unloading', 'unloaded', 'completed', 'cancelled'])->default('pending');

            // Timing
            $table->timestamp('arrived_at');
            $table->timestamp('unloading_started_at')->nullable();
            $table->timestamp('unloading_completed_at')->nullable();

            // Weight measurements (in metric tonnes)
            $table->decimal('gross_weight', 10, 2)->nullable(); // Truck + cargo
            $table->decimal('tare_weight', 10, 2)->nullable();   // Truck alone
            $table->decimal('net_weight', 10, 2)->nullable();    // Cargo only

            // Unloading details
            $table->decimal('unloaded_quantity', 10, 2)->nullable(); // Coal unloaded in MT
            $table->text('notes')->nullable();

            // User tracking
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();

            // Timestamps
            $table->timestamps();

            // Indexes
            $table->index(['siding_id', 'status']);
            $table->index(['vehicle_id']);
            $table->index(['arrived_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('vehicle_arrivals');
    }
};
