<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cost_allocations', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignId('cost_center_id')->constrained();

            $table->date('allocation_date');
            $table->enum('cost_type', ['fuel', 'maintenance', 'insurance', 'depreciation', 'registration', 'other']);
            $table->enum('source_type', ['fuel_transaction', 'work_order', 'insurance_premium', 'manual_entry']);
            $table->unsignedBigInteger('source_id')->nullable();

            $table->decimal('amount', 12, 2);
            $table->decimal('vat_amount', 12, 2)->default(0);
            $table->string('description', 500)->nullable();

            $table->string('reference_number', 100)->nullable();
            $table->string('invoice_number', 100)->nullable();
            $table->string('supplier_name', 200)->nullable();

            $table->foreignId('allocated_by')->constrained('users');
            $table->enum('approval_status', ['pending', 'approved', 'rejected'])->default('approved');
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('approved_at')->nullable();

            $table->timestamps();

            $table->index(['cost_center_id', 'allocation_date']);
            $table->index(['organization_id', 'cost_type', 'allocation_date']);
            $table->index(['source_type', 'source_id']);
            $table->index(['approval_status', 'allocated_by']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cost_allocations');
    }
};
