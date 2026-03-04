<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('telematics_devices', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();

            $table->foreignId('vehicle_id')->nullable()->constrained('vehicles')->nullOnDelete();

            $table->string('device_id', 100)->unique();
            $table->string('provider', 100);
            $table->string('status', 50);

            $table->dateTime('installed_at')->nullable();
            $table->dateTime('last_sync_at')->nullable();

            $table->json('metadata')->nullable();

            $table->boolean('is_active')->default(true);

            $table->timestamps();

            $table->index(['organization_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('telematics_devices');
    }
};
