<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sales', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('organization_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('client_contact_id')->constrained('contacts')->cascadeOnDelete();
            $table->foreignId('lot_id')->constrained()->cascadeOnDelete();
            $table->foreignId('project_id')->constrained()->cascadeOnDelete();
            $table->foreignId('developer_id')->nullable()->constrained()->nullOnDelete();
            $table->text('comm_in_notes')->nullable();
            $table->text('comm_out_notes')->nullable();
            $table->unsignedInteger('payment_terms')->nullable();
            $table->json('expected_commissions')->nullable();
            $table->date('finance_due_date')->nullable();
            $table->decimal('comms_in_total', 10, 2)->unsigned()->nullable();
            $table->decimal('comms_out_total', 10, 2)->unsigned()->nullable();
            $table->decimal('piab_comm', 10, 2)->unsigned()->nullable();
            $table->foreignId('affiliate_contact_id')->nullable()->constrained('contacts')->nullOnDelete();
            $table->decimal('affiliate_comm', 10, 2)->unsigned()->nullable();
            $table->foreignId('subscriber_contact_id')->nullable()->constrained('contacts')->nullOnDelete();
            $table->decimal('subscriber_comm', 10, 2)->unsigned()->nullable();
            $table->foreignId('sales_agent_contact_id')->nullable()->constrained('contacts')->nullOnDelete();
            $table->decimal('sales_agent_comm', 10, 2)->unsigned()->nullable();
            $table->foreignId('bdm_contact_id')->nullable()->constrained('contacts')->nullOnDelete();
            $table->decimal('bdm_comm', 10, 2)->unsigned()->nullable();
            $table->foreignId('referral_partner_contact_id')->nullable()->constrained('contacts')->nullOnDelete();
            $table->decimal('referral_partner_comm', 10, 2)->unsigned()->nullable();
            $table->foreignId('agent_contact_id')->nullable()->constrained('contacts')->nullOnDelete();
            $table->decimal('agent_comm', 10, 2)->unsigned()->nullable();
            $table->json('divide_percent')->nullable();
            $table->boolean('is_comments_enabled')->default(false);
            $table->text('comments')->nullable();
            $table->boolean('is_sas_enabled')->default(false);
            $table->boolean('is_sas_max')->default(false);
            $table->decimal('sas_percent', 8, 2)->unsigned()->nullable();
            $table->decimal('sas_fee', 10, 2)->unsigned()->nullable();
            $table->text('summary_note')->nullable();
            $table->timestamp('status_updated_at')->nullable();
            $table->json('custom_attributes')->nullable();
            $table->timestamps();
            $table->softDeletes();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();

            $table->index('organization_id');
            $table->index('lot_id');
            $table->index('project_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sales');
    }
};
