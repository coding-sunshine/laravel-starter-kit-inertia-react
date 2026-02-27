<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('garages', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();

            $table->string('name', 200);
            $table->string('type', 50); // internal, external, mobile
            $table->json('specializations')->nullable();

            $table->text('address')->nullable();
            $table->string('postcode', 20)->nullable();
            $table->string('city', 100)->nullable();
            $table->decimal('lat', 10, 8)->nullable();
            $table->decimal('lng', 11, 8)->nullable();

            $table->string('contact_name', 200)->nullable();
            $table->string('contact_phone', 20)->nullable();
            $table->string('contact_email')->nullable();

            $table->json('operating_hours')->nullable();
            $table->unsignedTinyInteger('capacity')->default(1);

            $table->string('certification_level', 50)->nullable(); // basic, mot, specialist, main_dealer
            $table->decimal('hourly_rate', 8, 2)->nullable();
            $table->boolean('preferred_supplier')->default(false);
            $table->decimal('quality_rating', 3, 2)->nullable();

            $table->boolean('is_active')->default(true);

            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();
            $table->foreignId('deleted_by')->nullable()->constrained('users')->nullOnDelete();

            $table->index(['organization_id', 'is_active']);
            $table->index(['lat', 'lng']);
            $table->index(['type', 'certification_level']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('garages');
    }
};
