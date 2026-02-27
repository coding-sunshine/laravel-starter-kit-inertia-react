<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('routes', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();

            $table->string('name', 200);
            $table->string('route_type', 50)->index();
            $table->text('description')->nullable();

            $table->foreignId('start_location_id')->nullable()->constrained('locations')->nullOnDelete();
            $table->foreignId('end_location_id')->nullable()->constrained('locations')->nullOnDelete();

            $table->decimal('estimated_distance_km', 8, 2)->nullable();
            $table->unsignedInteger('estimated_duration_minutes')->nullable();

            $table->boolean('is_active')->default(true);

            $table->timestamps();

            $table->index(['organization_id', 'is_active']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('routes');
    }
};
