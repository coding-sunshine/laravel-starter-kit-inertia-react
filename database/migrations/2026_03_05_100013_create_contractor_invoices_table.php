<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('contractor_invoices', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignId('contractor_id')->constrained();

            $table->string('invoice_number', 100);
            $table->date('invoice_date');
            $table->date('due_date')->nullable();

            $table->decimal('subtotal', 12, 2)->nullable();
            $table->decimal('tax_amount', 10, 2)->nullable();
            $table->decimal('total_amount', 12, 2);

            $table->string('status', 20)->default('pending'); // pending, approved, paid, disputed, cancelled

            $table->string('work_order_reference', 100)->nullable();
            $table->text('description')->nullable();

            $table->date('paid_date')->nullable();
            $table->string('payment_reference', 100)->nullable();

            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();
            $table->foreignId('deleted_by')->nullable()->constrained('users')->nullOnDelete();

            $table->index(['organization_id', 'invoice_date']);
            $table->index(['contractor_id', 'invoice_date']);
            $table->unique(['organization_id', 'invoice_number']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('contractor_invoices');
    }
};
