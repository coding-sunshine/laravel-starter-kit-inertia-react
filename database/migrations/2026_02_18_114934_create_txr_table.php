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
        Schema::create('txr', function (Blueprint $table): void {

            $table->id();

            $table->foreignId('rake_id')
                ->constrained('rakes')
                ->cascadeOnDelete();

            // Inspection timing
            $table->dateTime('inspection_time')->nullable();
            $table->dateTime('inspection_end_time')->nullable();

            // Status: in_progress, completed, rejected
            $table->string('status')->default('in_progress');

            // Notes
            $table->text('remarks')->nullable();

            // Audit
            $table->foreignId('created_by')->nullable()
                ->constrained('users')->nullOnDelete();

            $table->foreignId('updated_by')->nullable()
                ->constrained('users')->nullOnDelete();

            $table->timestamps();

            // Enforce 1:1 TXR per rake
            $table->unique('rake_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('txr');
    }
};
