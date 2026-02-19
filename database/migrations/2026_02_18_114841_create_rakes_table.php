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
        Schema::create('rakes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('siding_id')->constrained('sidings')->onDelete('cascade');
            $table->string('rake_number', 20)->unique();
            $table->string('rake_type')->nullable(); // e.g., "BOBRN", "BOXN"
            $table->integer('wagon_count')->default(0);
            $table->dateTime('loading_start_time')->nullable();
            $table->dateTime('loading_end_time')->nullable();
            $table->decimal('loaded_weight_mt', 12, 2)->nullable();
            $table->decimal('predicted_weight_mt', 12, 2)->nullable();
            $table->string('state')->default('pending'); // pending, loading, loaded, dispatched, in_transit, received, completed
            $table->integer('free_time_minutes')->default(180); // 3 hours default
            $table->integer('demurrage_hours')->default(0);
            $table->decimal('demurrage_penalty_amount', 12, 2)->default(0);
            $table->dateTime('rr_expected_date')->nullable();
            $table->dateTime('rr_actual_date')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->onDelete('set null');
            $table->foreignId('updated_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamps();
            $table->softDeletes();

            $table->index(['siding_id', 'state']);
            $table->index('rake_number');
            $table->index('loading_start_time');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('rakes');
    }
};
