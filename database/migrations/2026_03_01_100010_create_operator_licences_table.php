<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('operator_licences', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();

            $table->string('license_number', 50);
            $table->string('license_type', 50); // standard_national, standard_international, restricted
            $table->string('traffic_commissioner_area', 50); // north_eastern, north_western, west_midlands, eastern, western, southern, scottish

            $table->date('issue_date');
            $table->date('effective_date');
            $table->date('expiry_date');
            $table->date('last_review_date')->nullable();
            $table->date('next_review_date')->nullable();

            $table->unsignedInteger('authorized_vehicles');
            $table->unsignedInteger('authorized_vehicles_used')->default(0);
            $table->unsignedInteger('authorized_trailers')->default(0);
            $table->unsignedInteger('authorized_trailers_used')->default(0);

            $table->json('operating_centres'); // [{ name, address, max_vehicles, max_trailers }]

            $table->decimal('financial_requirement_amount', 12, 2)->nullable();
            $table->string('financial_evidence_type', 50)->nullable(); // bank_guarantee, insurance_policy, cash_deposit
            $table->date('financial_evidence_expiry')->nullable();

            $table->string('transport_manager_name', 200)->nullable();
            $table->string('transport_manager_cpc_number', 50)->nullable();
            $table->string('transport_manager_contact', 500)->nullable();

            $table->string('compliance_rating', 50)->nullable(); // green, amber, red
            $table->string('repute_status', 50)->nullable(); // satisfactory, loss_of_repute, under_review
            $table->json('conditions_attached')->nullable();
            $table->json('undertakings')->nullable();

            $table->string('status', 50); // active, suspended, revoked, surrendered, applied, pending_review

            $table->date('last_compliance_inspection_date')->nullable();
            $table->date('next_compliance_inspection_due')->nullable();
            $table->string('maintenance_intervals', 50)->nullable(); // 6_weekly, 8_weekly, etc.

            $table->text('notes')->nullable();

            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();
            $table->foreignId('deleted_by')->nullable()->constrained('users')->nullOnDelete();

            $table->unique(['organization_id', 'license_number']);
            $table->index(['organization_id', 'status']);
            $table->index(['expiry_date']);
            $table->index(['compliance_rating']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('operator_licences');
    }
};
