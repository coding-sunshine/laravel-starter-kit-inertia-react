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
        Schema::create('coal_stock_approx_details', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('siding_id')->nullable()->constrained('sidings')->onDelete('cascade');
            $table->date('date')->nullable();
            $table->decimal('railway_siding_opening_coal_stock', 10, 2)->nullable();
            $table->decimal('railway_siding_closing_coal_stock', 10, 2)->nullable();
            $table->decimal('coal_dispatch_qty', 10, 2)->nullable();
            $table->string('no_of_rakes')->nullable();
            $table->decimal('rakes_qty', 10, 2)->nullable();
            $table->enum('source', ['manual', 'system'])->nullable();
            $table->text('remarks')->nullable();
            $table->timestamps();

            $table->index('siding_id');
            $table->index('date');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('coal_stock_approx_details');
    }
};
