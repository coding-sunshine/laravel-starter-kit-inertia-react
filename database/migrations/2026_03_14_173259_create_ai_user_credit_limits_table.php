<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ai_user_credit_limits', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->integer('credits_limit');
            $table->integer('credits_used')->default(0);
            $table->date('period_start');
            $table->timestamps();

            $table->unique(['user_id', 'period_start']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ai_user_credit_limits');
    }
};
