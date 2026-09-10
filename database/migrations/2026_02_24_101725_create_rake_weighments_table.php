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
        Schema::create('rake_weighments', function (Blueprint $table) {

            $table->id();

            $table->foreignId('rake_id')
                ->constrained('rakes')
                ->cascadeOnDelete();

            $table->integer('attempt_no')->default(1);

            // Header data from PDF
            $table->dateTime('gross_weighment_datetime')->nullable();
            $table->dateTime('tare_weighment_datetime')->nullable();

            $table->string('train_name')->nullable();
            $table->string('direction')->nullable();
            $table->string('commodity')->nullable();

            $table->string('from_station')->nullable();
            $table->string('to_station')->nullable();
            $table->string('priority_number')->nullable();

            $table->string('pdf_file_path')->nullable();

            $table->string('status')->default('success');
            // success / technical_failed

            $table->foreignId('created_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->timestamps();

            $table->index(['rake_id', 'attempt_no']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('rake_weighments');
    }
};
