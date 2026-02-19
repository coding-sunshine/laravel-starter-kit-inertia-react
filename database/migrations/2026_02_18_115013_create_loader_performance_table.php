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
        Schema::create('loader_performance', function (Blueprint $table) {
            $table->id();
            $table->foreignId('loader_id')->constrained('loaders')->onDelete('cascade');
            $table->date('as_of_date');
            $table->integer('rakes_processed')->default(0);
            $table->integer('average_loading_time_minutes')->default(0);
            $table->integer('consistency_variance_minutes')->default(0); // ±minutes
            $table->integer('overload_incidents')->default(0);
            $table->integer('quality_score')->default(100); // 0-100
            $table->timestamps();

            $table->unique(['loader_id', 'as_of_date']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('loader_performance');
    }
};
