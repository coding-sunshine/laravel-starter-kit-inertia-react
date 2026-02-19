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
        Schema::create('rr_predictions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('rake_id')->constrained('rakes')->onDelete('cascade');
            $table->decimal('predicted_weight_mt', 12, 2);
            $table->date('predicted_rr_date');
            $table->string('prediction_confidence')->default('medium'); // low, medium, high
            $table->string('prediction_status')->default('pending'); // pending, confirmed_match, variance_detected
            $table->decimal('variance_percent', 5, 2)->nullable();
            $table->timestamps();

            $table->unique('rake_id');
            $table->index(['predicted_rr_date', 'prediction_status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('rr_predictions');
    }
};
