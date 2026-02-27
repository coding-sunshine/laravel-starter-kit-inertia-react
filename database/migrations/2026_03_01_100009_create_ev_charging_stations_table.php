<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ev_charging_stations', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('organization_id')->nullable()->constrained()->cascadeOnDelete();

            $table->string('external_id', 100)->nullable()->index();
            $table->string('name', 200);
            $table->string('operator', 100)->nullable();
            $table->string('network', 100)->nullable();

            $table->foreignId('location_id')->nullable()->constrained('locations')->nullOnDelete();
            $table->text('address')->nullable();
            $table->decimal('lat', 10, 8)->nullable();
            $table->decimal('lng', 11, 8)->nullable();

            $table->string('access_type', 50)->default('public'); // public, private, restricted
            $table->json('connector_types')->nullable();
            $table->json('charging_speeds')->nullable();
            $table->unsignedTinyInteger('total_connectors')->default(1);
            $table->unsignedTinyInteger('available_connectors')->default(1);

            $table->json('pricing_structure')->nullable();
            $table->json('operating_hours')->nullable();
            $table->json('amenities')->nullable();

            $table->string('status', 50)->default('operational'); // operational, maintenance, out_of_service
            $table->timestamp('last_status_update')->nullable();

            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();
            $table->foreignId('deleted_by')->nullable()->constrained('users')->nullOnDelete();

            $table->index(['lat', 'lng']);
            $table->index(['organization_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ev_charging_stations');
    }
};
