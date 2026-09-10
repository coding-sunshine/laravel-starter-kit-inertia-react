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
        Schema::create('siding_opening_balances', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('siding_id')->constrained('sidings')->cascadeOnDelete();
            $table->decimal('opening_balance_mt', 12, 2)->default(0);
            $table->date('as_of_date')->nullable();
            $table->text('remarks')->nullable();
            $table->timestamps();

            $table->unique('siding_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('siding_opening_balances');
    }
};
