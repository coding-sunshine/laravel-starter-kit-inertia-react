<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('commissions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sale_id')->constrained('sales')->cascadeOnDelete();
            $table->string('commission_type')->index()->comment('piab, subscriber, affiliate, sales_agent, referral_partner, bdm, sub_agent');
            $table->foreignId('agent_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->decimal('rate_percentage', 5, 2)->nullable();
            $table->decimal('amount', 12, 2)->default(0);
            $table->boolean('override_amount')->default(false);
            $table->text('notes')->nullable();
            $table->softDeletes();
            $table->timestamps();

            $table->index(['sale_id', 'commission_type']);
            $table->index(['sale_id', 'agent_user_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('commissions');
    }
};
