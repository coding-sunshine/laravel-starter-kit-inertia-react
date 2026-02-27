<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('wagon_loading', function (Blueprint $table) {

            $table->id();

            // Link directly to rake
            $table->foreignId('rake_id')
                ->constrained('rakes')
                ->cascadeOnDelete();

            // Which wagon
            $table->foreignId('wagon_id')
                ->constrained('wagons')
                ->cascadeOnDelete();

            // Loader used
            $table->foreignId('loader_id')
                ->nullable()
                ->constrained('loaders')
                ->nullOnDelete();

            // Snapshot fields (important for audit)
            $table->string('loader_operator_name')->nullable();
            $table->decimal('cc_capacity_mt', 10, 2)->nullable();

            // Actual loaded quantity
            $table->decimal('loaded_quantity_mt', 10, 2)->nullable();

            // When loading was completed
            $table->dateTime('loading_time')->nullable();

            $table->text('remarks')->nullable();

            $table->timestamps();

            // One loading record per wagon per rake
            $table->unique(['rake_id', 'wagon_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('rake_wagon_loading');
    }
};
