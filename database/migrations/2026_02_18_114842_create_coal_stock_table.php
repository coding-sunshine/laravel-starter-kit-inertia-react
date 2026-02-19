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
        Schema::create('coal_stock', function (Blueprint $table) {
            $table->id();
            $table->foreignId('siding_id')->constrained('sidings')->onDelete('cascade');
            $table->decimal('opening_balance_mt', 12, 2)->default(0);
            $table->decimal('receipt_quantity_mt', 12, 2)->default(0);
            $table->decimal('dispatch_quantity_mt', 12, 2)->default(0);
            $table->decimal('closing_balance_mt', 12, 2)->default(0);
            $table->date('as_of_date');
            $table->string('remarks')->nullable();
            $table->timestamps();

            $table->index(['siding_id', 'as_of_date']);
            $table->unique(['siding_id', 'as_of_date']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('coal_stock');
    }
};
