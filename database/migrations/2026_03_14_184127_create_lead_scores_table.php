<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('lead_scores', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('contact_id')->constrained()->cascadeOnDelete();
            $table->unsignedSmallInteger('score')->default(0);
            $table->json('factors_json')->nullable();
            $table->string('model_version')->default('rule-based-v1');
            $table->timestamp('scored_at')->useCurrent();
            $table->timestamps();

            $table->index('contact_id');
            $table->index('score');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('lead_scores');
    }
};
