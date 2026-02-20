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
        Schema::create('stock_ledgers', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('siding_id')->constrained();
            $table->enum('transaction_type', ['receipt', 'dispatch', 'correction']);

            // References to source transactions
            $table->foreignId('vehicle_arrival_id')->nullable()->constrained();
            $table->foreignId('rake_id')->nullable()->constrained();

            // Stock quantities (in metric tonnes)
            $table->decimal('quantity_mt', 10, 2);
            $table->decimal('opening_balance_mt', 10, 2);
            $table->decimal('closing_balance_mt', 10, 2);

            // Reference details
            $table->string('reference_number')->nullable();
            $table->text('remarks')->nullable();

            // User tracking
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('verified_by')->nullable();

            // Timestamps
            $table->timestamps();

            // Indexes for efficient queries
            $table->index(['siding_id', 'created_at']);
            $table->index(['transaction_type']);
            $table->index(['vehicle_arrival_id']);
            $table->index(['rake_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('stock_ledgers');
    }
};
