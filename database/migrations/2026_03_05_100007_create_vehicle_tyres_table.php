<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('vehicle_tyres', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('vehicle_id')->constrained()->cascadeOnDelete();
            $table->foreignId('tyre_inventory_id')->nullable()->constrained('tyre_inventory')->nullOnDelete();

            $table->string('position', 20); // front_left, front_right, rear_left, rear_right, spare, etc.
            $table->string('size', 50)->nullable();
            $table->string('brand', 100)->nullable();

            $table->date('fitted_at')->nullable();
            $table->decimal('tread_depth_mm', 5, 2)->nullable();
            $table->unsignedInteger('odometer_at_fit')->nullable();

            $table->text('notes')->nullable();

            $table->timestamps();

            $table->index(['vehicle_id', 'position']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('vehicle_tyres');
    }
};
