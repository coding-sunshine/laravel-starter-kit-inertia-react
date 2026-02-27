<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('driver_vehicle_assignments', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignId('driver_id')->constrained()->cascadeOnDelete();
            $table->foreignId('vehicle_id')->constrained()->cascadeOnDelete();

            $table->string('assignment_type', 50)->default('primary'); // primary, secondary, temporary
            $table->date('assigned_date');
            $table->date('unassigned_date')->nullable();
            $table->boolean('is_current')->default(true);

            $table->text('notes')->nullable();

            $table->foreignId('assigned_by')->constrained('users')->cascadeOnDelete();
            $table->timestamps();

            $table->index(['driver_id', 'vehicle_id', 'unassigned_date']);
            $table->index(['vehicle_id', 'assigned_date']);
            $table->index(['organization_id', 'is_current']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('driver_vehicle_assignments');
    }
};
