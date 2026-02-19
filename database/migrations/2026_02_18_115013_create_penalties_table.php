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
        Schema::create('penalties', function (Blueprint $table) {
            $table->id();
            $table->foreignId('rake_id')->constrained('rakes')->onDelete('cascade');
            $table->string('penalty_type', 50); // POL1, POLA, PLO, ULC, SPL, DEM, WMC, MCF, etc.
            $table->decimal('penalty_amount', 12, 2);
            $table->string('penalty_status')->default('pending'); // pending, incurred, waived, disputed
            $table->text('description')->nullable();
            $table->text('remediation_notes')->nullable();
            $table->date('penalty_date');
            $table->timestamps();
            $table->softDeletes();

            $table->index(['rake_id', 'penalty_status']);
            $table->index('penalty_type');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('penalties');
    }
};
