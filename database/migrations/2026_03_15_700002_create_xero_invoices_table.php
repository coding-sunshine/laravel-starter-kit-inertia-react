<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('xero_invoices', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('sale_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('xero_connection_id')->constrained()->cascadeOnDelete();
            $table->string('xero_invoice_id')->index();
            $table->string('invoice_number')->nullable();
            $table->decimal('amount', 12, 2)->nullable();
            $table->string('status')->default('DRAFT');
            $table->string('invoice_type')->default('ACCREC');
            $table->timestamp('issued_at')->nullable();
            $table->timestamp('due_at')->nullable();
            $table->timestamp('last_synced_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('xero_invoices');
    }
};
