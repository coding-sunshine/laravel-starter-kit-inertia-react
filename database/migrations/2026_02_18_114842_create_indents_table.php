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
        Schema::create('indents', function (Blueprint $table): void {

            $table->id();

            $table->foreignId('siding_id')
                ->constrained('sidings')
                ->cascadeOnDelete();

            // Official indent reference
            $table->string('indent_number', 20)->nullable();

            // Official demand details
            $table->string('demanded_stock', 100)->nullable();   // Rake / wagon type
            $table->integer('total_units')->nullable();          // Number of wagons requested

            // Quantities
            $table->decimal('target_quantity_mt', 12, 2)->nullable();
            $table->decimal('allocated_quantity_mt', 12, 2)->nullable();
            $table->decimal('available_stock_mt', 12, 2)->nullable();

            // Dates & timing
            $table->dateTime('indent_date')->nullable();
            $table->dateTime('indent_time')->nullable();
            $table->date('expected_loading_date')->nullable();
            $table->dateTime('required_by_date')->nullable();

            // Railway references
            $table->string('railway_reference_no', 100)->nullable();
            $table->string('e_demand_reference_id', 100)->nullable();
            $table->string('fnr_number', 50)->nullable();

            // Workflow state
            $table->string('state')->nullable();

            // Notes
            $table->text('remarks')->nullable();

            // Audit
            $table->foreignId('created_by')->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->foreignId('updated_by')->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->timestamps();
            $table->softDeletes();
            $table->index(['siding_id', 'state']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('indents');
    }
};
