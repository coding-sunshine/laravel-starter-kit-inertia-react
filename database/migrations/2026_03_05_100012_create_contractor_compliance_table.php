<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('contractor_compliance', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignId('contractor_id')->constrained();

            $table->string('compliance_type', 50); // insurance, certification, license, safety
            $table->string('status', 20)->default('valid'); // valid, expiring_soon, expired, missing

            $table->string('reference_number', 100)->nullable();
            $table->date('issue_date')->nullable();
            $table->date('expiry_date')->nullable();

            $table->string('document_url', 500)->nullable();
            $table->text('notes')->nullable();

            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();
            $table->foreignId('deleted_by')->nullable()->constrained('users')->nullOnDelete();

            $table->index(['contractor_id', 'compliance_type']);
            $table->index(['organization_id', 'expiry_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('contractor_compliance');
    }
};
