<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('risk_assessments', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();

            $table->string('subject_type', 100); // vehicle, driver, location, task, etc.
            $table->unsignedBigInteger('subject_id');

            $table->string('title', 200);
            $table->string('type', 50); // generic, driving, maintenance, site
            $table->string('reference_number', 100)->nullable();
            $table->text('description')->nullable();
            $table->text('hazards')->nullable();
            $table->text('control_measures')->nullable();
            $table->json('risk_matrix')->nullable();

            $table->string('status', 20)->default('draft'); // draft, pending_approval, approved, under_review, archived
            $table->date('review_date')->nullable();

            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('approved_at')->nullable();

            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();
            $table->foreignId('deleted_by')->nullable()->constrained('users')->nullOnDelete();

            $table->index(['organization_id', 'status']);
            $table->index(['subject_type', 'subject_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('risk_assessments');
    }
};
