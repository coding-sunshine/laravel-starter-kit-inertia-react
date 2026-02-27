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
        Schema::create('wagons', function (Blueprint $table): void {

            $table->id();

            $table->foreignId('rake_id')
                ->constrained('rakes')
                ->cascadeOnDelete();

            $table->integer('wagon_sequence')->nullable();
            // Position inside rake

            $table->string('wagon_number', 20)->unique();

            $table->string('wagon_type')->nullable();

            $table->decimal('tare_weight_mt', 10, 2)->nullable();

            $table->decimal('pcc_weight_mt', 10, 2)->nullable();
            // Permitted Carrying Capacity

            // TXR result snapshot
            $table->boolean('is_unfit')->default(false);

            // Simple lifecycle indicator
            $table->string('state')->default('pending');

            $table->timestamps();

            $table->index(['rake_id', 'wagon_sequence']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('wagons');
    }
};
