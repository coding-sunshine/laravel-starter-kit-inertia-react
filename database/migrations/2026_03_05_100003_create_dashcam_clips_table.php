<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('dashcam_clips', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignId('vehicle_id')->constrained();
            $table->foreignId('driver_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('incident_id')->nullable()->constrained()->nullOnDelete();

            $table->string('clip_id', 100)->nullable()->unique();
            $table->string('event_type', 50);
            $table->string('status', 20)->default('available');

            $table->string('clip_url', 1000)->nullable();
            $table->string('thumbnail_url', 1000)->nullable();
            $table->timestamp('recorded_at');
            $table->unsignedInteger('duration_seconds')->nullable();
            $table->unsignedInteger('file_size_bytes')->nullable();

            $table->decimal('lat', 10, 8)->nullable();
            $table->decimal('lng', 11, 8)->nullable();
            $table->decimal('speed_kmh', 6, 2)->nullable();

            $table->json('metadata')->nullable();

            $table->timestamps();

            $table->index(['organization_id', 'recorded_at']);
            $table->index(['vehicle_id', 'recorded_at']);
            $table->index(['driver_id', 'recorded_at']);
            $table->index(['incident_id']);
            $table->index(['event_type', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('dashcam_clips');
    }
};
