<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ai_bot_prompts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('bot_id')->constrained('ai_bots')->cascadeOnDelete();
            $table->string('label');
            $table->text('prompt_template');
            $table->integer('sort_order')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ai_bot_prompts');
    }
};
