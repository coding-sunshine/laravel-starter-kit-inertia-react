<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('work_order_parts', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('work_order_id')->constrained()->cascadeOnDelete();
            $table->foreignId('parts_inventory_id')->constrained('parts_inventory');

            $table->decimal('quantity_used', 10, 2)->default(1);
            $table->decimal('unit_cost', 10, 2)->nullable();
            $table->decimal('total_cost', 10, 2)->nullable();
            $table->text('notes')->nullable();

            $table->timestamps();

            $table->index(['work_order_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('work_order_parts');
    }
};
