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
        Schema::create('rakes', function (Blueprint $table): void {

            $table->id();

            // Relationships
            $table->foreignId('siding_id')
                ->constrained('sidings')
                ->cascadeOnDelete();

            $table->foreignId('indent_id')
                ->nullable()
                ->index();

            // Identification
            $table->string('rake_number', 20)->unique();
            $table->string('rake_type', 50)->nullable();

            // Composition
            $table->integer('wagon_count')->nullable();

            // Process timestamps
            $table->dateTime('placement_time')->nullable();
            $table->dateTime('dispatch_time')->nullable();

            // Weights
            $table->decimal('loaded_weight_mt', 12, 2)->nullable();
            $table->decimal('predicted_weight_mt', 12, 2)->nullable();

            // RR related
            $table->dateTime('rr_expected_date')->nullable();
            $table->dateTime('rr_actual_date')->nullable();

            // Lifecycle
            $table->string('state')->nullable();

            // Timers
            $table->dateTime('loading_start_time')->nullable();
            $table->dateTime('loading_end_time')->nullable();
            $table->integer('loading_free_minutes')->default(180);
            $table->dateTime('guard_start_time')->nullable();
            $table->dateTime('guard_end_time')->nullable();
            $table->dateTime('weighment_start_time')->nullable();
            $table->dateTime('weighment_end_time')->nullable();
            // Audit
            $table->foreignId('created_by')->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->foreignId('updated_by')->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->foreignId('deleted_by')->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->timestamps();
            $table->softDeletes();

            // Optional minimal index
            $table->index(['siding_id', 'state']);
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
