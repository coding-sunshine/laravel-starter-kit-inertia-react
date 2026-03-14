<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ai_summaries', function (Blueprint $table): void {
            $table->id();
            $table->string('summarizable_type');
            $table->unsignedBigInteger('summarizable_id');
            $table->text('content');
            $table->string('model')->default('gpt-4o-mini');
            $table->timestamp('created_at');

            $table->index(['summarizable_type', 'summarizable_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ai_summaries');
    }
};
