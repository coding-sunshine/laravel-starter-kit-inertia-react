<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('fuel_transactions', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignId('vehicle_id')->constrained();
            $table->foreignId('driver_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('fuel_card_id')->constrained();

            $table->string('external_transaction_id', 100)->nullable();
            $table->timestamp('transaction_timestamp');

            $table->foreignId('fuel_station_id')->nullable()->constrained()->nullOnDelete();
            $table->string('fuel_station_name', 200)->nullable();
            $table->text('fuel_station_address')->nullable();
            $table->decimal('lat', 9, 6)->nullable();
            $table->decimal('lng', 9, 6)->nullable();

            $table->string('fuel_type', 50); // petrol, diesel, electric, adblue, lpg
            $table->decimal('litres', 8, 3)->nullable();
            $table->decimal('price_per_litre', 6, 3);
            $table->decimal('total_cost', 8, 2);
            $table->decimal('vat_amount', 8, 2)->nullable();

            $table->unsignedInteger('odometer_reading')->nullable();

            $table->string('pump_number', 10)->nullable();
            $table->string('receipt_number', 100)->nullable();
            $table->string('authorization_code', 50)->nullable();
            $table->string('transaction_method', 50)->nullable(); // chip_pin, contactless, mobile, manual

            $table->decimal('fuel_efficiency_kmpl', 6, 3)->nullable();
            $table->decimal('distance_since_last_fill', 8, 2)->nullable();
            $table->decimal('tank_capacity_percent', 5, 2)->nullable();

            $table->decimal('fraud_risk_score', 5, 2)->default(0);
            $table->json('anomaly_flags')->nullable();
            $table->string('validation_status', 50)->default('approved'); // pending, approved, flagged, rejected
            $table->foreignId('validated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->text('validation_notes')->nullable();

            $table->timestamps();

            $table->index(['vehicle_id', 'transaction_timestamp']);
            $table->index(['fuel_card_id', 'transaction_timestamp']);
            $table->index(['organization_id', 'transaction_timestamp']);
            $table->index(['validation_status', 'fraud_risk_score']);
            $table->unique(['external_transaction_id', 'fuel_card_id'], 'fuel_transactions_external_card_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('fuel_transactions');
    }
};
