<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('property_reservations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('agent_contact_id')->nullable()->constrained('contacts')->nullOnDelete();
            $table->foreignId('primary_contact_id')->nullable()->constrained('contacts')->nullOnDelete();
            $table->foreignId('secondary_contact_id')->nullable()->constrained('contacts')->nullOnDelete();
            $table->foreignId('logged_in_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('lot_id')->nullable()->constrained('lots')->nullOnDelete();
            $table->foreignId('project_id')->nullable()->constrained('projects')->nullOnDelete();
            $table->string('stage')->default('enquiry')->index();
            $table->decimal('purchase_price', 12, 2)->nullable();
            $table->json('purchaser_type')->nullable();
            $table->string('trustee_name')->nullable();
            $table->string('abn_acn')->nullable();
            $table->boolean('smsf_trust_setup')->default(false);
            $table->boolean('bare_trust_setup')->default(false);
            $table->boolean('funds_rollover')->default(false);
            $table->boolean('agree_lawlab')->default(false);
            $table->string('firm')->nullable();
            $table->string('broker')->nullable();
            $table->boolean('finance_condition')->default(false);
            $table->integer('finance_days')->nullable();
            $table->decimal('deposit', 12, 2)->nullable();
            $table->decimal('deposit_bal', 12, 2)->nullable();
            $table->decimal('build_deposit', 12, 2)->nullable();
            $table->date('payment_duedate')->nullable();
            $table->date('contract_send')->nullable();
            $table->boolean('agree')->default(false);
            $table->date('agree_date')->nullable();
            $table->string('deposit_status')->default('pending')->comment('pending, paid, failed');
            $table->string('eway_transaction_id')->nullable();
            $table->string('eway_access_code')->nullable();
            $table->text('notes')->nullable();
            $table->unsignedBigInteger('legacy_id')->nullable()->index();
            $table->unsignedBigInteger('created_by')->nullable()->index();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->softDeletes();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('property_reservations');
    }
};
