<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('behavior_events', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();

            $table->foreignId('vehicle_id')->constrained('vehicles')->cascadeOnDelete();
            $table->foreignId('driver_id')->nullable()->constrained('drivers')->nullOnDelete();
            // Trip is optional and created in a separate migration with the same timestamp;
            // to avoid ordering issues we store the ID without a DB-level foreign key.
            $table->unsignedBigInteger('trip_id')->nullable();

            $table->string('event_type', 100);
            $table->string('severity', 50)->nullable();
            $table->dateTime('occurred_at');

            $table->decimal('lat', 10, 8)->nullable();
            $table->decimal('lng', 11, 8)->nullable();
            $table->decimal('speed_kmh', 6, 2)->nullable();

            $table->json('metadata')->nullable();

            $table->timestamps();

            $table->index(['organization_id', 'vehicle_id', 'occurred_at']);
            $table->index(['organization_id', 'driver_id', 'occurred_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('behavior_events');
    }
};
