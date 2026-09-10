<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('siding_risk_scores', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('siding_id')->constrained()->cascadeOnDelete();
            $table->unsignedSmallInteger('score'); // 0-100
            $table->json('risk_factors')->nullable();
            $table->string('trend', 20)->default('stable'); // improving, stable, worsening
            $table->timestamp('calculated_at');
            $table->timestamps();

            $table->index(['siding_id', 'calculated_at']);
            $table->unique(['siding_id', 'calculated_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('siding_risk_scores');
    }
};
