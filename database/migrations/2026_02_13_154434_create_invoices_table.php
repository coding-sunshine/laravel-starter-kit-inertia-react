<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('invoices', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->morphs('billable');
            $table->string('number')->unique();
            $table->string('status')->default('draft');
            $table->string('currency', 3)->default('usd');
            $table->unsignedInteger('subtotal');
            $table->unsignedInteger('tax')->default(0);
            $table->unsignedInteger('total');
            $table->timestamp('paid_at')->nullable();
            $table->date('due_date')->nullable();
            $table->json('line_items')->nullable();
            $table->json('billing_address')->nullable();
            $table->unsignedBigInteger('payment_gateway_id')->nullable();
            $table->string('gateway_invoice_id')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('invoices');
    }
};
