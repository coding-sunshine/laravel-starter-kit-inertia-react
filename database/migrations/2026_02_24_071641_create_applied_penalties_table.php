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
        Schema::create('applied_penalties', function (Blueprint $table) {
            $table->id();

            $table->foreignId('penalty_type_id')
                ->constrained('penalty_types')
                ->cascadeOnDelete();

            // Mandatory: Rake Level
            $table->foreignId('rake_id')
                ->constrained('rakes')
                ->cascadeOnDelete();

            // Optional: Wagon Level (For POL1 etc.)
            $table->foreignId('wagon_id')
                ->nullable()
                ->constrained('wagons')
                ->cascadeOnDelete();

            // Inputs used for calculation
            $table->decimal('quantity', 12, 2)->nullable();
            $table->decimal('distance', 12, 2)->nullable();
            $table->decimal('rate', 12, 2)->nullable();

            // Final Calculated Amount
            $table->decimal('amount', 14, 2);

            // Store calculation breakdown
            $table->json('meta')->nullable();

            $table->timestamps();

            $table->index(['rake_id', 'wagon_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('applied_penalties');
    }
};
