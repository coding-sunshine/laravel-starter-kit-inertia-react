<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('training_courses', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();

            $table->string('course_name', 200);
            $table->string('course_code', 50)->nullable();
            $table->text('description')->nullable();

            $table->enum('category', ['safety', 'compliance', 'skills', 'induction', 'refresher']);
            $table->decimal('duration_hours', 5, 2);
            $table->enum('delivery_method', ['classroom', 'online', 'practical', 'blended']);

            $table->json('prerequisites')->nullable();
            $table->json('learning_objectives')->nullable();

            $table->boolean('assessment_required')->default(false);
            $table->unsignedTinyInteger('pass_mark_percentage')->default(70);
            $table->boolean('certificate_awarded')->default(true);
            $table->unsignedSmallInteger('validity_period_months')->nullable();

            $table->decimal('cost_per_person', 8, 2)->nullable();
            $table->string('provider_name', 200)->nullable();
            $table->string('provider_contact', 200)->nullable();

            $table->unsignedTinyInteger('max_participants')->nullable();
            $table->json('materials_required')->nullable();
            $table->json('equipment_required')->nullable();

            $table->boolean('is_mandatory')->default(false);
            $table->boolean('is_active')->default(true);

            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();
            $table->foreignId('deleted_by')->nullable()->constrained('users')->nullOnDelete();

            $table->index(['organization_id', 'is_active', 'category']);
            $table->index(['course_code']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('training_courses');
    }
};
