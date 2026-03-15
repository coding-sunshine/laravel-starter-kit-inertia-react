<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('suburb_ai_data', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('organization_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('suburb_id')->nullable()->constrained()->nullOnDelete();
            $table->string('suburb_name');
            $table->string('state')->nullable();
            $table->string('postcode')->nullable();
            $table->string('source')->default('ai');
            $table->decimal('median_house_price', 12, 2)->nullable();
            $table->decimal('median_unit_price', 12, 2)->nullable();
            $table->decimal('median_rent_house', 10, 2)->nullable();
            $table->decimal('median_rent_unit', 10, 2)->nullable();
            $table->decimal('rental_yield', 5, 2)->nullable();
            $table->decimal('annual_growth', 5, 2)->nullable();
            $table->json('price_rent_json')->nullable();
            $table->json('ai_insights')->nullable();
            $table->timestamp('fetched_at')->nullable();
            $table->timestamps();

            $table->index(['suburb_name', 'organization_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('suburb_ai_data');
    }
};
