<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('locations', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();

            $table->string('name', 200);
            $table->string('type', 50); // depot, customer, service_station, parking, warehouse, other
            $table->text('address');
            $table->string('postcode', 20)->nullable();
            $table->string('city', 100)->nullable();
            $table->string('country', 50)->default('GB');

            $table->decimal('lat', 10, 8)->nullable();
            $table->decimal('lng', 11, 8)->nullable();

            $table->string('contact_name', 200)->nullable();
            $table->string('contact_phone', 20)->nullable();
            $table->string('contact_email')->nullable();

            $table->json('operating_hours')->nullable();
            $table->text('access_restrictions')->nullable();
            $table->text('notes')->nullable();
            $table->boolean('is_active')->default(true);

            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();
            $table->foreignId('deleted_by')->nullable()->constrained('users')->nullOnDelete();

            $table->index(['organization_id', 'type', 'is_active']);
            $table->index(['lat', 'lng']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('locations');
    }
};
