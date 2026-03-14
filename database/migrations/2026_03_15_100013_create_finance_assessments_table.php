<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('finance_assessments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignId('contact_id')->nullable()->constrained()->nullOnDelete();
            $table->string('name');
            $table->string('email');
            $table->string('phone')->nullable();
            $table->string('employment_type');
            $table->decimal('annual_income', 12, 2)->nullable();
            $table->decimal('other_income', 12, 2)->nullable();
            $table->decimal('existing_loans', 12, 2)->nullable();
            $table->decimal('credit_card_limit', 12, 2)->nullable();
            $table->decimal('deposit_available', 12, 2)->nullable();
            $table->string('property_purpose')->nullable();
            $table->string('preferred_loan_type')->nullable();
            $table->text('notes')->nullable();
            $table->timestamp('submitted_at')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->bigInteger('legacy_id')->nullable()->unique();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('finance_assessments');
    }
};
