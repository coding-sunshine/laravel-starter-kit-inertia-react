<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('route_stops', function (Blueprint $table): void {
            $table->id();

            // Use unsignedBigInteger here to avoid migration ordering issues;
            // application-level logic still treats this as a foreign key to routes.id.
            $table->unsignedBigInteger('route_id');
            $table->foreignId('location_id')->nullable()->constrained('locations')->nullOnDelete();

            $table->string('name', 200)->nullable();
            $table->unsignedInteger('sort_order')->default(0);

            $table->dateTime('planned_arrival_time')->nullable();
            $table->dateTime('planned_departure_time')->nullable();
            $table->dateTime('actual_arrival_time')->nullable();
            $table->dateTime('actual_departure_time')->nullable();

            $table->text('notes')->nullable();

            $table->timestamps();

            $table->index(['route_id', 'sort_order']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('route_stops');
    }
};
