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
        Schema::create('vehicle_unload', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('siding_id')->constrained('sidings')->onDelete('cascade');
            $table->foreignId('vehicle_id')->constrained('vehicles')->onDelete('cascade');
            $table->string('jimms_challan_number', 30)->nullable();
            $table->dateTime('arrival_time');
            $table->dateTime('unload_start_time')->nullable();
            $table->dateTime('unload_end_time')->nullable();
            $table->decimal('mine_weight_mt', 12, 2)->nullable();
            $table->decimal('weighment_weight_mt', 12, 2)->nullable();
            $table->decimal('variance_mt', 12, 2)->nullable(); // Calculated in application layer
            $table->string('state')->default('pending'); // pending, unloading, completed, cancelled
            $table->string('remarks')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->onDelete('set null');
            $table->foreignId('updated_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamps();
            $table->softDeletes();

            $table->index(['siding_id', 'state']);
            $table->index(['vehicle_id', 'arrival_time']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('vehicle_unload');
    }
};
