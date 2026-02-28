<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('fuel_cards', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();

            $table->string('card_number', 50)->unique();
            $table->string('provider', 100);
            $table->string('card_type', 50)->default('fleet'); // fleet, individual, emergency
            $table->string('status', 50)->default('active'); // active, blocked, expired, lost

            $table->date('issue_date')->nullable();
            $table->date('expiry_date')->nullable();
            $table->boolean('pin_required')->default(true);

            $table->decimal('daily_limit', 8, 2)->nullable();
            $table->decimal('weekly_limit', 8, 2)->nullable();
            $table->decimal('monthly_limit', 8, 2)->nullable();
            $table->decimal('transaction_limit', 8, 2)->nullable();

            $table->json('fuel_type_restrictions')->nullable();
            $table->json('location_restrictions')->nullable();
            $table->json('time_restrictions')->nullable();

            $table->foreignId('assigned_vehicle_id')->nullable()->constrained('vehicles')->nullOnDelete();
            $table->foreignId('assigned_driver_id')->nullable()->constrained('drivers')->nullOnDelete();

            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();
            $table->foreignId('deleted_by')->nullable()->constrained('users')->nullOnDelete();

            $table->index(['organization_id', 'status']);
            $table->index(['assigned_vehicle_id', 'assigned_driver_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('fuel_cards');
    }
};
