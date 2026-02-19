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
        Schema::create('siding_performance', function (Blueprint $table) {
            $table->id();
            $table->foreignId('siding_id')->constrained('sidings')->onDelete('cascade');
            $table->date('as_of_date');
            $table->integer('rakes_processed')->default(0);
            $table->decimal('total_penalty_amount', 12, 2)->default(0);
            $table->integer('penalty_incidents')->default(0);
            $table->integer('average_demurrage_hours')->default(0);
            $table->integer('overload_incidents')->default(0);
            $table->decimal('closing_stock_mt', 12, 2)->default(0);
            $table->timestamps();

            $table->unique(['siding_id', 'as_of_date']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('siding_performance');
    }
};
