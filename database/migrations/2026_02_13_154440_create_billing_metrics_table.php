<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('billing_metrics', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->date('date');
            $table->unsignedInteger('mrr')->default(0);
            $table->unsignedInteger('arr')->default(0);
            $table->unsignedInteger('new_subscriptions')->default(0);
            $table->unsignedInteger('churned')->default(0);
            $table->unsignedInteger('credits_purchased')->default(0);
            $table->unsignedInteger('credits_used')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('billing_metrics');
    }
};
