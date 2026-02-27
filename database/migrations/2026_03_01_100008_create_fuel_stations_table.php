<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('fuel_stations', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('organization_id')->nullable()->constrained()->cascadeOnDelete(); // nullable for public stations

            $table->string('external_id', 100)->nullable()->index();
            $table->string('name', 200);
            $table->string('brand', 100)->nullable();

            $table->text('address');
            $table->string('postcode', 20)->nullable();
            $table->string('city', 100)->nullable();
            $table->string('country', 50)->default('GB');
            $table->decimal('lat', 10, 8)->nullable();
            $table->decimal('lng', 11, 8)->nullable();

            $table->json('fuel_types_available')->nullable();
            $table->json('facilities')->nullable();
            $table->json('operating_hours')->nullable();

            $table->string('phone', 20)->nullable();
            $table->string('website')->nullable();

            $table->decimal('price_quality_rating', 3, 2)->nullable();
            $table->boolean('is_active')->default(true);

            $table->timestamps();

            $table->index(['organization_id']);
            $table->index(['lat', 'lng']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('fuel_stations');
    }
};
