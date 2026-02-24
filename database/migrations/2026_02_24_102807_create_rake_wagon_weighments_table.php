<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('rake_wagon_weighments', function (Blueprint $table) {
            $table->id();

            // Parent weighment attempt
            $table->foreignId('rake_weighment_id')
                ->constrained('rake_weighments')
                ->cascadeOnDelete();

            // Wagon reference
            $table->foreignId('wagon_id')
                ->constrained('wagons')
                ->cascadeOnDelete();

            // Measured gross weight
            $table->decimal('gross_weight_mt', 10, 2);

            // Overload flag (calculated during processing)
            $table->boolean('is_overloaded')->default(false);

            $table->timestamps();

            // Prevent duplicate wagon entries per weighment attempt
            $table->unique(['rake_weighment_id', 'wagon_id']);

            // Helpful index
            $table->index('wagon_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('rake_wagon_weighments');
    }
};
