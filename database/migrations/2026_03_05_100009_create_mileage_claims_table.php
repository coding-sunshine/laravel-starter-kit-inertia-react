<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('mileage_claims', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignId('grey_fleet_vehicle_id')->constrained();
            $table->foreignId('user_id')->constrained();
            $table->date('claim_date');
            $table->unsignedInteger('start_odometer')->nullable();
            $table->unsignedInteger('end_odometer')->nullable();
            $table->unsignedInteger('distance_km')->nullable();
            $table->string('purpose', 500)->nullable();
            $table->string('destination', 200)->nullable();
            $table->decimal('amount_claimed', 10, 2)->nullable();
            $table->decimal('amount_approved', 10, 2)->nullable();
            $table->string('status', 20)->default('pending');
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('approved_at')->nullable();
            $table->text('rejection_reason')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();
            $table->foreignId('deleted_by')->nullable()->constrained('users')->nullOnDelete();
            $table->index(['organization_id', 'claim_date']);
            $table->index(['grey_fleet_vehicle_id', 'claim_date']);
            $table->index(['user_id', 'claim_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('mileage_claims');
    }
};
