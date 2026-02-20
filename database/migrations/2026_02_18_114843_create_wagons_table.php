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
            $table->foreignId('rake_id')->constrained('rakes')->onDelete('cascade');
            $table->integer('wagon_sequence')->default(0); // Position in rake (1-60)
            $table->string('wagon_number', 20)->unique();
            $table->string('wagon_type')->nullable(); // BOXN, BOBRN, etc.
            $table->decimal('tare_weight_mt', 10, 2)->nullable();
            $table->decimal('loaded_weight_mt', 10, 2)->nullable();
            $table->decimal('pcc_weight_mt', 10, 2)->nullable(); // Permitted Carrying Capacity
            $table->decimal('loader_recorded_qty_mt', 10, 2)->nullable();
            $table->decimal('weighment_qty_mt', 10, 2)->nullable();
            $table->boolean('is_unfit')->default(false); // TXR inspection result
            $table->boolean('is_overloaded')->default(false); // Overload flag
            $table->string('state')->default('pending'); // pending, loading, loaded, unfit, completed
            $table->foreignId('loader_id')->nullable()->constrained('loaders')->onDelete('set null');
            $table->timestamps();

            $table->index(['rake_id', 'wagon_sequence']);
            $table->index('wagon_number');
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
