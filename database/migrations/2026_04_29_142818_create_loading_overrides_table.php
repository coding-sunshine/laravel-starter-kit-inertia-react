<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('loading_overrides', function (Blueprint $table) {
            $table->id();
            $table->foreignId('rake_id')->constrained()->cascadeOnDelete();
            $table->foreignId('wagon_loading_id')->nullable()->constrained('wagon_loading')->nullOnDelete();
            $table->foreignId('operator_id')->constrained('users')->cascadeOnDelete();
            $table->enum('reason', ['reduced_load', 'equipment_constraint', 'railway_instruction', 'other']);
            $table->text('notes')->nullable();
            $table->decimal('overload_mt', 8, 3)->default(0);
            $table->decimal('estimated_penalty_at_time', 10, 2)->default(0);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('loading_overrides');
    }
};
