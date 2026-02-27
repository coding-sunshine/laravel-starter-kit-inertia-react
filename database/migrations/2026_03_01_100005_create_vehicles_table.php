<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('vehicles', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();

            $table->string('registration')->index();
            $table->string('vin', 17)->nullable();
            $table->string('fleet_number')->nullable();

            $table->string('make');
            $table->string('model');
            $table->unsignedInteger('year')->nullable();
            $table->string('fuel_type', 50); // petrol, diesel, electric, hybrid
            $table->string('vehicle_type', 50); // car, van, truck, bus, motorcycle

            $table->unsignedInteger('weight_kg')->nullable();
            $table->unsignedInteger('max_payload_kg')->nullable();
            $table->unsignedTinyInteger('seating_capacity')->nullable();

            $table->string('status', 50)->default('active'); // active, maintenance, vor, disposed
            $table->foreignId('current_driver_id')->nullable()->constrained('drivers')->nullOnDelete();
            $table->foreignId('home_location_id')->nullable()->constrained('locations')->nullOnDelete();

            $table->decimal('current_lat', 10, 8)->nullable();
            $table->decimal('current_lng', 11, 8)->nullable();
            $table->timestamp('location_updated_at')->nullable();
            $table->unsignedSmallInteger('location_accuracy_meters')->nullable();

            $table->unsignedInteger('odometer_reading')->default(0);
            $table->timestamp('odometer_updated_at')->nullable();
            $table->unsignedInteger('monthly_distance_km')->default(0);
            $table->decimal('monthly_fuel_cost', 8, 2)->default(0);

            $table->date('purchase_date')->nullable();
            $table->decimal('purchase_price', 12, 2)->nullable();
            $table->decimal('current_value', 12, 2)->nullable();
            $table->decimal('depreciation_rate', 5, 4)->nullable();

            $table->string('insurance_group', 10)->nullable();
            $table->date('mot_expiry_date')->nullable();
            $table->date('insurance_expiry_date')->nullable();
            $table->date('tax_expiry_date')->nullable();
            $table->string('compliance_status', 50)->default('compliant');

            $table->unsignedSmallInteger('co2_emissions')->nullable();
            $table->string('euro_standard', 10)->nullable();

            $table->decimal('maintenance_risk_score', 5, 2)->default(0);
            $table->decimal('efficiency_score', 5, 2)->default(50);
            $table->decimal('safety_score', 5, 2)->default(50);
            $table->timestamp('scores_updated_at')->nullable();

            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();
            $table->foreignId('deleted_by')->nullable()->constrained('users')->nullOnDelete();

            $table->unique(['organization_id', 'registration']);
            $table->index(['organization_id', 'status']);
            $table->index(['current_lat', 'current_lng', 'location_updated_at']);
            $table->index(['compliance_status', 'mot_expiry_date']);
            $table->index(['maintenance_risk_score', 'safety_score']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('vehicles');
    }
};
