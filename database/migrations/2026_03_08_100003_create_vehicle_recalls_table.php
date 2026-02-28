<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('vehicle_recalls', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignId('vehicle_id')->nullable()->constrained()->nullOnDelete();

            $table->string('recall_reference', 100);
            $table->string('make', 100)->nullable();
            $table->string('model', 100)->nullable();
            $table->string('title', 500)->nullable();
            $table->text('description')->nullable();
            $table->date('issued_date')->nullable();
            $table->date('due_date')->nullable();
            $table->string('status', 20)->default('pending'); // pending, in_progress, completed, not_applicable
            $table->date('completed_at')->nullable();
            $table->text('completion_notes')->nullable();

            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();
            $table->foreignId('deleted_by')->nullable()->constrained('users')->nullOnDelete();

            $table->index(['organization_id', 'status']);
            $table->index(['vehicle_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('vehicle_recalls');
    }
};
