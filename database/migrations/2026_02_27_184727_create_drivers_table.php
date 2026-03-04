<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('drivers', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();

            $table->string('employee_id')->nullable();
            $table->string('first_name');
            $table->string('last_name');
            $table->string('email')->nullable();
            $table->string('phone', 20)->nullable();
            $table->date('date_of_birth')->nullable();

            $table->date('hire_date')->nullable();
            $table->date('termination_date')->nullable();
            $table->string('status', 50)->default('active'); // active, suspended, terminated, on_leave

            $table->string('license_number', 50);
            $table->date('license_expiry_date');
            $table->string('license_status', 50)->default('valid'); // valid, expired, suspended, revoked
            $table->json('license_categories')->nullable();
            $table->string('cpc_number', 50)->nullable();
            $table->date('cpc_expiry_date')->nullable();
            $table->date('medical_certificate_expiry')->nullable();

            $table->string('emergency_contact_name', 200)->nullable();
            $table->string('emergency_contact_phone', 20)->nullable();

            $table->text('address')->nullable();
            $table->string('postcode', 20)->nullable();

            $table->decimal('safety_score', 5, 2)->default(100);
            $table->string('risk_category', 50)->default('low'); // low, medium, high, critical
            $table->unsignedInteger('accidents_count')->default(0);
            $table->unsignedInteger('violations_count')->default(0);
            $table->unsignedInteger('training_completed_count')->default(0);
            $table->date('last_incident_date')->nullable();

            $table->string('compliance_status', 50)->default('compliant'); // compliant, expiring_soon, expired
            $table->date('last_dvla_check')->nullable();

            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();
            $table->foreignId('deleted_by')->nullable()->constrained('users')->nullOnDelete();

            $table->unique(['organization_id', 'employee_id']);
            $table->index(['organization_id', 'status']);
            $table->index(['compliance_status', 'license_expiry_date']);
            $table->index(['risk_category', 'safety_score']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('drivers');
    }
};
