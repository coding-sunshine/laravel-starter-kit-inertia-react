<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('compliance_items', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();

            $table->string('entity_type', 50); // vehicle, driver, organization, trailer
            $table->unsignedBigInteger('entity_id');
            $table->string('compliance_type', 50); // mot, license, cpc, insurance, etc.

            $table->string('title', 200);
            $table->text('description')->nullable();
            $table->string('regulatory_body', 100)->nullable();
            $table->boolean('legal_requirement')->default(true);

            $table->date('issue_date')->nullable();
            $table->date('expiry_date');
            $table->date('renewal_date')->nullable();

            $table->string('status', 50)->default('valid'); // valid, expiring_soon, expired, renewed, revoked
            $table->unsignedSmallInteger('days_warning')->default(30);

            $table->decimal('cost', 8, 2)->nullable();
            $table->decimal('renewal_cost', 8, 2)->nullable();

            $table->boolean('renewal_required')->default(true);
            $table->boolean('auto_renewal_enabled')->default(false);
            $table->unsignedSmallInteger('reminder_frequency_days')->default(7);
            $table->timestamp('last_reminder_sent')->nullable();

            $table->string('document_reference', 100)->nullable();
            $table->string('issuing_authority', 200)->nullable();
            $table->string('certificate_number', 100)->nullable();

            $table->text('notes')->nullable();

            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();
            $table->foreignId('deleted_by')->nullable()->constrained('users')->nullOnDelete();

            $table->unique(['entity_type', 'entity_id', 'compliance_type']);
            $table->index(['expiry_date', 'status', 'renewal_required']);
            $table->index(['organization_id', 'compliance_type', 'status']);
            $table->index(['entity_type', 'entity_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('compliance_items');
    }
};
