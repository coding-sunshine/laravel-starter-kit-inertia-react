<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sequence_steps', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('nurture_sequence_id')->constrained()->cascadeOnDelete();
            $table->unsignedSmallInteger('step_order')->default(1);
            $table->string('channel'); // email, sms, task
            $table->string('subject')->nullable();
            $table->text('template_body');
            $table->unsignedSmallInteger('delay_days')->default(1);
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->index(['nurture_sequence_id', 'step_order']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sequence_steps');
    }
};
