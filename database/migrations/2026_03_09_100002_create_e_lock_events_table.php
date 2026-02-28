<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('e_lock_events', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignId('vehicle_id')->constrained();

            $table->string('event_type', 50); // lock, unlock, tamper, geofence_unlock
            $table->timestamp('event_timestamp');
            $table->decimal('lat', 10, 8)->nullable();
            $table->decimal('lng', 11, 8)->nullable();
            $table->string('device_id', 100)->nullable();
            $table->json('metadata')->nullable();
            $table->boolean('alert_sent')->default(false);

            $table->timestamps();

            $table->index(['organization_id', 'event_timestamp']);
            $table->index(['vehicle_id', 'event_timestamp']);
            $table->index(['event_type', 'event_timestamp']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('e_lock_events');
    }
};
