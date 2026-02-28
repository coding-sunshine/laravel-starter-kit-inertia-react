<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('carbon_targets', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();

            $table->string('name', 200);
            $table->text('description')->nullable();
            $table->enum('period', ['monthly', 'quarterly', 'annual'])->default('annual');
            $table->unsignedSmallInteger('target_year');
            $table->decimal('target_co2_kg', 14, 2);
            $table->decimal('baseline_co2_kg', 14, 2)->nullable();
            $table->boolean('is_active')->default(true);

            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['organization_id', 'target_year', 'period']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('carbon_targets');
    }
};
