<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('commissions', function (Blueprint $table): void {
            $table->id();
            $table->string('commissionable_type');
            $table->unsignedBigInteger('commissionable_id');
            $table->decimal('commission_in', 8, 2)->unsigned()->nullable();
            $table->decimal('commission_out', 8, 2)->unsigned()->nullable();
            $table->decimal('commission_profit', 8, 2)->unsigned()->nullable();
            $table->decimal('commission_percent_in', 8, 2)->unsigned()->nullable();
            $table->decimal('commission_percent_out', 8, 2)->unsigned()->nullable();
            $table->decimal('commission_percent_profit', 8, 2)->unsigned()->nullable();
            $table->json('extra_attributes')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['commissionable_type', 'commissionable_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('commissions');
    }
};
