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
        Schema::create('freight_rate_master', function (Blueprint $table): void {
            $table->id();
            $table->string('commodity_code', 20); // e.g., "COAL-BOBRN"
            $table->string('commodity_name');
            $table->string('class_code', 20); // e.g., "145A"
            $table->string('risk_rate', 50)->nullable();
            $table->decimal('distance_from_km', 10, 2);
            $table->decimal('distance_to_km', 10, 2);
            $table->decimal('rate_per_mt', 10, 2); // Freight rate per MT
            $table->decimal('gst_percent', 5, 2)->default(5.00);
            $table->date('effective_from');
            $table->date('effective_to')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();

            $table->index(['commodity_code', 'is_active']);
            $table->index(['class_code', 'is_active']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('freight_rate_master');
    }
};
