<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sales', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->nullable()->constrained()->nullOnDelete();
            $table->unsignedBigInteger('legacy_id')->nullable()->index();
            $table->foreignId('client_contact_id')->nullable()->constrained('contacts')->nullOnDelete();
            $table->foreignId('lot_id')->nullable()->constrained('lots')->nullOnDelete();
            $table->foreignId('project_id')->nullable()->constrained('projects')->nullOnDelete();
            $table->foreignId('developer_id')->nullable()->constrained('developers')->nullOnDelete();
            $table->foreignId('sales_agent_contact_id')->nullable()->constrained('contacts')->nullOnDelete();
            $table->foreignId('subscriber_contact_id')->nullable()->constrained('contacts')->nullOnDelete();
            $table->foreignId('bdm_contact_id')->nullable()->constrained('contacts')->nullOnDelete();
            $table->foreignId('referral_partner_contact_id')->nullable()->constrained('contacts')->nullOnDelete();
            $table->foreignId('affiliate_contact_id')->nullable()->constrained('contacts')->nullOnDelete();
            $table->foreignId('agent_contact_id')->nullable()->constrained('contacts')->nullOnDelete();
            $table->string('status')->default('active')->index();
            $table->text('comm_in_notes')->nullable();
            $table->text('comm_out_notes')->nullable();
            $table->text('payment_terms')->nullable();
            $table->json('expected_commissions')->nullable();
            $table->date('finance_due_date')->nullable();
            $table->decimal('comms_in_total', 12, 2)->nullable();
            $table->decimal('comms_out_total', 12, 2)->nullable();
            $table->decimal('piab_comm', 12, 2)->nullable();
            $table->decimal('affiliate_comm', 12, 2)->nullable();
            $table->decimal('subscriber_comm', 12, 2)->nullable();
            $table->decimal('sales_agent_comm', 12, 2)->nullable();
            $table->decimal('bdm_comm', 12, 2)->nullable();
            $table->decimal('referral_partner_comm', 12, 2)->nullable();
            $table->decimal('agent_comm', 12, 2)->nullable();
            $table->json('divide_percent')->nullable();
            $table->boolean('is_comments_enabled')->default(true);
            $table->text('comments')->nullable();
            $table->boolean('is_sas_enabled')->default(false);
            $table->boolean('is_sas_max')->default(false);
            $table->decimal('sas_percent', 5, 2)->nullable();
            $table->decimal('sas_fee', 12, 2)->nullable();
            $table->text('summary_note')->nullable();
            $table->timestamp('status_updated_at')->nullable();
            $table->timestamp('settled_at')->nullable();
            $table->unsignedBigInteger('created_by')->nullable()->index();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->softDeletes();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sales');
    }
};
