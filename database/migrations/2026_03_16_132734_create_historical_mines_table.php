<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('historical_mines', function (Blueprint $table): void {
            $table->id();
            $table->date('month')->nullable();
            $table->unsignedInteger('trips_dispatched')->nullable();
            $table->decimal('dispatched_qty', 12, 2)->nullable();
            $table->unsignedInteger('trips_received')->nullable();
            $table->decimal('received_qty', 12, 2)->nullable();
            $table->decimal('coal_production_qty', 12, 2)->nullable();
            $table->decimal('ob_production_qty', 12, 2)->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('historical_mines');
    }
};
