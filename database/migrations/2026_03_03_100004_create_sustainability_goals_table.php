<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sustainability_goals', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();

            $table->string('title', 200);
            $table->text('description')->nullable();
            $table->enum('status', ['draft', 'active', 'achieved', 'cancelled'])->default('draft');
            $table->date('target_date')->nullable();
            $table->decimal('target_value', 14, 2)->nullable();
            $table->string('target_unit', 50)->nullable();
            $table->json('metrics')->nullable();

            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['organization_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sustainability_goals');
    }
};
