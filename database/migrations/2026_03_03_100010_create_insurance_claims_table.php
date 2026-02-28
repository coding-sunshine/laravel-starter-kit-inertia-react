<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('insurance_claims', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignId('incident_id')->constrained();
            $table->foreignId('insurance_policy_id')->constrained();

            $table->string('claim_number', 100)->unique();
            $table->enum('claim_type', ['motor', 'liability', 'goods_in_transit', 'employers_liability']);

            $table->decimal('claim_amount', 12, 2)->nullable();
            $table->decimal('excess_amount', 10, 2)->nullable();
            $table->decimal('settlement_amount', 12, 2)->nullable();

            $table->enum('status', ['draft', 'submitted', 'acknowledged', 'investigating', 'approved', 'rejected', 'settled', 'closed'])->default('draft');
            $table->date('submitted_date')->nullable();
            $table->date('acknowledged_date')->nullable();
            $table->date('settlement_date')->nullable();

            $table->string('claim_handler_name', 200)->nullable();
            $table->string('claim_handler_contact', 200)->nullable();
            $table->string('assessor_name', 200)->nullable();
            $table->json('assessor_report')->nullable();

            $table->json('supporting_documents')->nullable();
            $table->json('correspondence_log')->nullable();
            $table->decimal('recovery_amount', 12, 2)->nullable();

            $table->boolean('legal_action_required')->default(false);
            $table->string('legal_representative', 200)->nullable();

            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();
            $table->foreignId('deleted_by')->nullable()->constrained('users')->nullOnDelete();

            $table->index(['organization_id', 'status']);
            $table->index(['incident_id']);
            $table->index(['submitted_date', 'settlement_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('insurance_claims');
    }
};
