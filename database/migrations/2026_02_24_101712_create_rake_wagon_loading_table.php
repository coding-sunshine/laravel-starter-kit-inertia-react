<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('rake_wagon_loading', function (Blueprint $table) {
            $table->id();

            $table->foreignId('rake_load_id')
                ->constrained('rake_loads')
                ->cascadeOnDelete();

            $table->foreignId('wagon_id')
                ->constrained('wagons')
                ->cascadeOnDelete();

            $table->foreignId('loader_id')
                ->nullable()
                ->constrained('loaders')
                ->nullOnDelete();

            $table->decimal('loaded_quantity_mt', 10, 2);

            $table->integer('attempt_no')->default(1);
            // If overload & reload, attempt increases

            $table->dateTime('started_at')->nullable();
            $table->dateTime('completed_at')->nullable();

            $table->timestamps();

            $table->index(['rake_load_id', 'wagon_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('rake_wagon_loading');
    }
};
