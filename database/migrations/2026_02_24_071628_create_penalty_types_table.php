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
        Schema::create('penalty_types', function (Blueprint $table) {
            $table->id();

            $table->string('code')->unique(); // POL1, POLA, DEM, etc.
            $table->string('name');

            $table->enum('category', [
                'overloading',
                'time_service',
                'operational',
                'safety',
                'other',
            ]);

            $table->text('description')->nullable();

            $table->enum('calculation_type', [
                'formula_based',
                'fixed',
                'per_hour',
                'per_mt',
            ]);

            $table->decimal('default_rate', 12, 2)->nullable();

            $table->boolean('is_active')->default(true);

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('penalty_types');
    }
};
