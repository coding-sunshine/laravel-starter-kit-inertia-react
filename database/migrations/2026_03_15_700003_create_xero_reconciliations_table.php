<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('xero_reconciliations', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('xero_invoice_id')->constrained()->cascadeOnDelete();
            $table->string('xero_payment_id')->index();
            $table->decimal('amount', 12, 2);
            $table->date('payment_date')->nullable();
            $table->timestamp('reconciled_at')->nullable();
            $table->json('raw_payload')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('xero_reconciliations');
    }
};
