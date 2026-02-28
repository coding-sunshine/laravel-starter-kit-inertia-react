<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('driver_qualifications', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignId('driver_id')->constrained()->cascadeOnDelete();

            $table->enum('qualification_type', ['license', 'cpc', 'training', 'certification', 'medical']);
            $table->string('qualification_name', 200);
            $table->string('issuing_authority', 200)->nullable();
            $table->string('qualification_number', 100)->nullable();

            $table->date('issue_date')->nullable();
            $table->date('expiry_date')->nullable();
            $table->enum('status', ['valid', 'expired', 'suspended', 'revoked', 'pending'])->default('valid');

            $table->string('grade_achieved', 50)->nullable();
            $table->unsignedTinyInteger('score_achieved')->nullable();
            $table->string('certificate_file_path', 500)->nullable();

            $table->boolean('verification_required')->default(false);
            $table->foreignId('verified_by')->nullable()->constrained('users')->nullOnDelete();
            $table->date('verification_date')->nullable();

            $table->text('notes')->nullable();

            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();
            $table->foreignId('deleted_by')->nullable()->constrained('users')->nullOnDelete();

            $table->index(['driver_id', 'qualification_type', 'status']);
            $table->index(['expiry_date', 'status']);
            $table->index(['organization_id', 'qualification_type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('driver_qualifications');
    }
};
