<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('training_sessions', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignId('training_course_id')->constrained()->cascadeOnDelete();

            $table->string('session_name', 200);
            $table->string('instructor_name', 200)->nullable();
            $table->string('instructor_contact', 200)->nullable();

            $table->date('scheduled_date');
            $table->time('start_time');
            $table->time('end_time');
            $table->string('location', 300)->nullable();

            $table->unsignedTinyInteger('max_participants')->nullable();
            $table->unsignedTinyInteger('registered_count')->default(0);
            $table->unsignedTinyInteger('attended_count')->default(0);

            $table->enum('status', ['scheduled', 'confirmed', 'in_progress', 'completed', 'cancelled', 'postponed'])->default('scheduled');

            $table->decimal('completion_rate', 5, 2)->nullable();
            $table->decimal('average_score', 5, 2)->nullable();
            $table->decimal('feedback_score', 3, 2)->nullable();

            $table->text('notes')->nullable();
            $table->json('materials_provided')->nullable();

            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();
            $table->foreignId('deleted_by')->nullable()->constrained('users')->nullOnDelete();

            $table->index(['training_course_id', 'scheduled_date']);
            $table->index(['organization_id', 'status', 'scheduled_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('training_sessions');
    }
};
