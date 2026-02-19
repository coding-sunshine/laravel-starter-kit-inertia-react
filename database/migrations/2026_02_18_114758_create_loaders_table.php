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
        Schema::create('loaders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('siding_id')->constrained('sidings')->onDelete('cascade');
            $table->string('loader_name');
            $table->string('code', 10)->unique(); // e.g., "L16", "L11", "L17"
            $table->string('loader_type'); // e.g., "Backhoe", "Excavator", "Wheel Loader"
            $table->string('make_model')->nullable();
            $table->date('last_calibration_date')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();

            $table->index(['siding_id', 'is_active']);
            $table->index('code');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('loaders');
    }
};
