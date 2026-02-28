<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('axle_load_readings', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignId('vehicle_id')->constrained();
            $table->foreignId('trip_id')->nullable()->constrained()->nullOnDelete();

            $table->timestamp('recorded_at');
            $table->json('axle_weights_kg')->nullable();
            $table->decimal('total_weight_kg', 10, 2)->nullable();
            $table->boolean('overload_flag')->default(false);
            $table->decimal('legal_limit_kg', 10, 2)->nullable();
            $table->json('metadata')->nullable();

            $table->timestamps();

            $table->index(['organization_id', 'recorded_at']);
            $table->index(['vehicle_id', 'recorded_at']);
            $table->index(['overload_flag', 'recorded_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('axle_load_readings');
    }
};
