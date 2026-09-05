<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('penalty_predictions', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('siding_id')->constrained()->cascadeOnDelete();
            $table->date('prediction_date');
            $table->string('risk_level', 10); // high, medium, low
            $table->json('predicted_types')->nullable();
            $table->decimal('predicted_amount_min', 12, 2)->default(0);
            $table->decimal('predicted_amount_max', 12, 2)->default(0);
            $table->json('factors')->nullable();
            $table->json('recommendations')->nullable();
            $table->timestamps();

            $table->index(['siding_id', 'prediction_date']);
            $table->index('prediction_date');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('penalty_predictions');
    }
};
