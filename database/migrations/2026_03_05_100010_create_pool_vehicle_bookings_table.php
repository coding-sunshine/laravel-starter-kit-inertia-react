<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pool_vehicle_bookings', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignId('vehicle_id')->constrained();
            $table->foreignId('user_id')->constrained();

            $table->timestamp('booking_start');
            $table->timestamp('booking_end');
            $table->string('status', 20)->default('booked'); // booked, checked_out, returned, cancelled, no_show

            $table->string('purpose', 500)->nullable();
            $table->string('destination', 200)->nullable();
            $table->unsignedInteger('odometer_start')->nullable();
            $table->unsignedInteger('odometer_end')->nullable();

            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();
            $table->foreignId('deleted_by')->nullable()->constrained('users')->nullOnDelete();

            $table->index(['organization_id', 'booking_start']);
            $table->index(['vehicle_id', 'booking_start']);
            $table->index(['user_id', 'booking_start']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pool_vehicle_bookings');
    }
};
