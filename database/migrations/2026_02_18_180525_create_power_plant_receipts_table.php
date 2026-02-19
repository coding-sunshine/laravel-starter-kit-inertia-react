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
        Schema::create('power_plant_receipts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('rake_id')->constrained('rakes')->cascadeOnDelete();
            $table->foreignId('power_plant_id')->constrained('power_plants')->cascadeOnDelete();
            $table->date('receipt_date');
            $table->decimal('weight_mt', 12, 2);
            $table->string('rr_reference', 50)->nullable();
            $table->decimal('variance_mt', 12, 2)->nullable();
            $table->decimal('variance_pct', 8, 2)->nullable();
            $table->string('status', 30)->default('pending'); // pending, verified, discrepancy
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['rake_id', 'power_plant_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('power_plant_receipts');
    }
};
