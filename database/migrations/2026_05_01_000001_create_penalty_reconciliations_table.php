<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('penalty_reconciliations', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('rake_id')->constrained()->cascadeOnDelete();
            $table->string('penalty_code', 16);
            $table->decimal('predicted_amount', 12, 2)->nullable();
            $table->decimal('billed_amount', 12, 2)->nullable();
            $table->decimal('variance', 12, 2)->nullable();
            $table->decimal('variance_pct', 6, 2)->nullable();
            $table->boolean('dispute_candidate')->default(false);
            $table->json('notes')->nullable();
            $table->dateTime('reconciled_at');
            $table->timestamps();
            $table->userstamps();

            $table->unique(['rake_id', 'penalty_code'], 'penalty_reconciliations_rake_code_unique');
            $table->index(['penalty_code', 'dispute_candidate']);
            $table->index('reconciled_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('penalty_reconciliations');
    }
};
