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
        Schema::create('wagon_unfit_logs', function (Blueprint $table) {

            $table->id();

            // Link to TXR session
            $table->foreignId('txr_id')
                ->constrained('txr')
                ->cascadeOnDelete();

            // Wagon marked unfit
            $table->foreignId('wagon_id')
                ->constrained('wagons')
                ->cascadeOnDelete();

            // Why it is unfit
            $table->text('reason')->nullable();

            // How it was marked (Flag / Light / Manual etc.)
            $table->string('marking_method')->nullable();

            // When it was marked
            $table->dateTime('marked_at')->nullable();

            // Audit
            $table->foreignId('created_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->timestamps();

            // One wagon should not be marked unfit twice in same TXR
            $table->unique(['txr_id', 'wagon_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('wagon_unfit_logs');
    }
};
