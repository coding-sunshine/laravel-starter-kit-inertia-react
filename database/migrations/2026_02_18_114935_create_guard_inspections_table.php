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
        Schema::create('guard_inspections', function (Blueprint $table): void {

            $table->id();

            $table->foreignId('rake_id')
                ->constrained('rakes')
                ->cascadeOnDelete();

            // Guard phase timing
            $table->dateTime('inspection_start_time')->nullable();
            $table->dateTime('inspection_end_time')->nullable();

            // Permission to move
            $table->dateTime('movement_permission_time')->nullable();

            $table->boolean('is_approved')->default(false);

            $table->text('remarks')->nullable();

            // Audit
            $table->foreignId('created_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->timestamps();

            // Only one guard inspection per rake
            $table->unique('rake_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('guard_inspections');
    }
};
