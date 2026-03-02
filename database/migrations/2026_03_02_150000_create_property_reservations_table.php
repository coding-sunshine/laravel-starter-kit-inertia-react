<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('property_reservations', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('organization_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('agent_contact_id')->constrained('contacts')->cascadeOnDelete();
            $table->foreignId('primary_contact_id')->constrained('contacts')->cascadeOnDelete();
            $table->foreignId('secondary_contact_id')->nullable()->constrained('contacts')->nullOnDelete();
            $table->foreignId('logged_in_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('project_id')->constrained()->cascadeOnDelete();
            $table->foreignId('lot_id')->constrained()->cascadeOnDelete();
            $table->string('purchase_price');
            $table->json('purchaser_type')->nullable();
            $table->string('trustee_name')->nullable();
            $table->string('abn_acn')->nullable();
            $table->json('SMSF_trust_setup')->nullable();
            $table->json('bare_trust_setup')->nullable();
            $table->json('funds_rollover')->nullable();
            $table->json('agree_lawlab')->nullable();
            $table->json('firm')->nullable();
            $table->json('broker')->nullable();
            $table->string('finance_preapproval')->nullable();
            $table->string('finance_days_req')->nullable();
            $table->string('deposit')->nullable();
            $table->string('land_deposit')->nullable();
            $table->string('build_deposit')->nullable();
            $table->json('contract_send')->nullable();
            $table->string('agree')->nullable();
            $table->date('agree_date')->nullable();
            $table->json('family_trust')->nullable();
            $table->timestamps();
            $table->softDeletes();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();

            $table->index('organization_id');
            $table->index('project_id');
            $table->index('lot_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('property_reservations');
    }
};
