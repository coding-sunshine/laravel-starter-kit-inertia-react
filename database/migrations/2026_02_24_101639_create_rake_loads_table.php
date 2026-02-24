<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('rake_loads', function (Blueprint $table) {
            $table->id();

            $table->foreignId('rake_id')
                ->constrained('rakes')
                ->cascadeOnDelete();

            // Timer starts here
            $table->dateTime('placement_time');

            // Default 3 hours (can override per rake)
            $table->integer('free_time_minutes')->default(180);

            $table->string('status')->default('in_progress');
            // in_progress, completed

            $table->timestamps();

            $table->unique('rake_id'); // one active loading per rake
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('rake_loads');
    }
};
