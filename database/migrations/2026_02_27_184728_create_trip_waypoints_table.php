<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('trip_waypoints', function (Blueprint $table): void {
            $table->id();

            // Trip belongs to a separate migration with the same timestamp; store ID without FK.
            $table->unsignedBigInteger('trip_id');

            $table->decimal('lat', 10, 8);
            $table->decimal('lng', 11, 8);
            $table->dateTime('recorded_at');

            $table->unsignedInteger('sequence')->default(0);
            $table->decimal('speed_kmh', 6, 2)->nullable();
            $table->unsignedSmallInteger('heading')->nullable();
            $table->integer('altitude_m')->nullable();
            $table->integer('accuracy_m')->nullable();
            $table->json('raw_payload')->nullable();

            $table->timestamps();

            $table->index(['trip_id', 'sequence']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('trip_waypoints');
    }
};
