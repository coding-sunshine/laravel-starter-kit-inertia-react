<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('brochure_processings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->string('file_path');
            $table->enum('type', ['project', 'lot']);
            $table->json('extracted_data');
            $table->enum('status', ['pending_approval', 'approved', 'rejected', 'created'])->default('pending_approval');
            $table->foreignId('processed_by_user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('approved_by_user_id')->nullable()->constrained('users')->cascadeOnDelete();
            $table->foreignId('created_project_id')->nullable()->constrained('projects')->cascadeOnDelete();
            $table->foreignId('created_lot_id')->nullable()->constrained('lots')->cascadeOnDelete();
            $table->text('admin_notes')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->timestamp('created_at_record')->nullable();
            $table->timestamps();

            $table->index(['organization_id', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('brochure_processings');
    }
};
