<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('loadrite_anomalies', function (Blueprint $table): void {
            $table->id();

            $table->foreignId('siding_id')
                ->constrained('sidings')
                ->cascadeOnDelete();

            $table->foreignId('loadrite_event_id')
                ->nullable()
                ->constrained('loadrite_events')
                ->nullOnDelete();

            /**
             * kind is a controlled string — one of:
             *   wagon_type_unmappable | operator_unmappable | bogus_timestamp | rake_serial_missing
             */
            $table->string('kind', 64);

            /** The raw bad input that could not be normalised. */
            $table->string('raw_value')->nullable();

            /** Extra debug context (event payload, resolved values, etc.). */
            $table->json('context')->nullable();

            /** Workflow status: open | resolved | ignored. */
            $table->string('status', 16)->default('open');

            $table->timestamp('resolved_at')->nullable();

            $table->foreignId('resolved_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->timestamps();

            // Indexes for query patterns used by CollectSignals and admin views.
            $table->index(['siding_id', 'status']);
            $table->index(['loadrite_event_id']);
            $table->index(['status', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('loadrite_anomalies');
    }
};
