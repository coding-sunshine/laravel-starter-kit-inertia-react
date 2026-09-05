<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('rake_wagon_weighments', function (Blueprint $table) {

            $table->id();

            $table->foreignId('rake_weighment_id')
                ->constrained('rake_weighments')
                ->cascadeOnDelete();

            $table->foreignId('wagon_id')
                ->constrained('wagons')
                ->cascadeOnDelete();

            // Wagon metadata snapshot from PDF
            $table->integer('wagon_sequence')->nullable();
            $table->string('wagon_type')->nullable();
            $table->integer('axles')->nullable();
            $table->decimal('cc_capacity_mt', 10, 2)->nullable();

            // Weighment data
            $table->decimal('printed_tare_mt', 10, 2)->nullable();
            $table->decimal('actual_gross_mt', 10, 2)->nullable();
            $table->decimal('actual_tare_mt', 10, 2)->nullable();
            $table->decimal('net_weight_mt', 10, 2)->nullable();

            $table->decimal('under_load_mt', 10, 2)->nullable();
            $table->decimal('over_load_mt', 10, 2)->nullable();

            $table->decimal('speed_kmph', 5, 2)->nullable();

            $table->dateTime('weighment_time')->nullable();
            $table->string('slip_number')->nullable();

            // Only editable field by user
            $table->text('action_taken')->nullable();

            $table->timestamps();

            $table->unique(['rake_weighment_id', 'wagon_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('rake_wagon_weighments');
    }
};
