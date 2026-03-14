<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sequence_enrollments', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('contact_id')->constrained()->cascadeOnDelete();
            $table->foreignId('nurture_sequence_id')->constrained()->cascadeOnDelete();
            $table->unsignedSmallInteger('current_step')->default(0);
            $table->string('status')->default('active'); // active, paused, completed, cancelled
            $table->timestamp('started_at')->useCurrent();
            $table->timestamp('next_run_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();

            $table->unique(['contact_id', 'nurture_sequence_id']);
            $table->index('next_run_at');
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sequence_enrollments');
    }
};
