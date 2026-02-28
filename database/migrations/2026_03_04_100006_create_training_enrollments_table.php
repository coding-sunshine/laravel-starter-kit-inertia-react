<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('training_enrollments', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignId('training_session_id')->constrained()->cascadeOnDelete();
            $table->foreignId('driver_id')->constrained()->cascadeOnDelete();

            $table->date('enrollment_date');
            $table->enum('enrollment_status', ['enrolled', 'confirmed', 'attended', 'completed', 'failed', 'cancelled', 'no_show'])->default('enrolled');

            $table->boolean('attendance_marked')->default(false);
            $table->timestamp('start_time')->nullable();
            $table->timestamp('end_time')->nullable();

            $table->unsignedTinyInteger('completion_percentage')->default(0);
            $table->unsignedTinyInteger('assessment_score')->nullable();
            $table->enum('pass_fail', ['pass', 'fail', 'pending', 'not_required'])->default('pending');

            $table->boolean('certificate_issued')->default(false);
            $table->string('certificate_number', 100)->nullable();

            $table->unsignedTinyInteger('feedback_rating')->nullable();
            $table->text('feedback_comments')->nullable();

            $table->foreignId('enrolled_by')->constrained('users');
            $table->timestamps();

            $table->unique(['training_session_id', 'driver_id']);
            $table->index(['driver_id', 'enrollment_status']);
            $table->index(['training_session_id', 'enrollment_status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('training_enrollments');
    }
};
