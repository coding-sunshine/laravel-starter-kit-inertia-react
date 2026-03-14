<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ai_bot_runs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('bot_id')->constrained('ai_bots')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->text('input_context')->nullable();
            $table->text('prompt_used');
            $table->text('output');
            $table->string('model_used');
            $table->boolean('realtime_data_injected')->default(false);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ai_bot_runs');
    }
};
