<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('service_schedules', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignId('vehicle_id')->constrained()->cascadeOnDelete();

            $table->string('service_type', 50); // mot, service, inspection, pmi
            $table->string('interval_type', 50); // mileage, time, both
            $table->unsignedInteger('interval_value');
            $table->string('interval_unit', 20); // days, weeks, months, km, miles

            $table->date('last_service_date')->nullable();
            $table->unsignedInteger('last_service_mileage')->nullable();

            $table->date('next_service_due_date')->nullable();
            $table->unsignedInteger('next_service_due_mileage')->nullable();

            $table->unsignedSmallInteger('alert_days_before')->default(30);
            $table->unsignedSmallInteger('alert_km_before')->default(1000);

            $table->foreignId('preferred_garage_id')->nullable()->constrained('garages')->nullOnDelete();
            $table->decimal('estimated_cost', 8, 2)->nullable();

            $table->boolean('is_mandatory')->default(false);
            $table->boolean('is_active')->default(true);

            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();
            $table->foreignId('deleted_by')->nullable()->constrained('users')->nullOnDelete();

            $table->index(['vehicle_id', 'service_type', 'is_active']);
            $table->index(['next_service_due_date', 'is_active']);
            $table->index(['next_service_due_mileage', 'is_active']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('service_schedules');
    }
};
