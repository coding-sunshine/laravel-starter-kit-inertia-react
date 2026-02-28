<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('parking_allocations', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignId('vehicle_id')->constrained();
            $table->foreignId('location_id')->constrained();

            $table->timestamp('allocated_from');
            $table->timestamp('allocated_to')->nullable();
            $table->string('spot_identifier', 100)->nullable();
            $table->decimal('cost', 10, 2)->nullable();

            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();
            $table->foreignId('deleted_by')->nullable()->constrained('users')->nullOnDelete();

            $table->index(['organization_id', 'vehicle_id']);
            $table->index(['location_id', 'allocated_from']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('parking_allocations');
    }
};
