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
        Schema::create('section_timers', function (Blueprint $table): void {
            $table->id();
            $table->string('section_name'); // loading, guard, weighment
            $table->unsignedInteger('free_minutes'); // free time allowed
            $table->unsignedInteger('warning_minutes'); // warning threshold
            $table->boolean('penalty_applicable')->default(true); // whether penalty applies
            $table->timestamps();

            $table->unique('section_name');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('section_timers');
    }
};
