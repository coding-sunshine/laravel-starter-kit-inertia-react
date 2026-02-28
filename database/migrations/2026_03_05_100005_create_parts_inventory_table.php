<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('parts_inventory', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignId('garage_id')->nullable()->constrained()->nullOnDelete();

            $table->string('part_number', 100);
            $table->string('description', 500)->nullable();
            $table->string('category', 100)->nullable();

            $table->unsignedInteger('quantity')->default(0);
            $table->unsignedInteger('min_quantity')->default(0);
            $table->string('unit', 20)->default('each');

            $table->decimal('unit_cost', 10, 2)->nullable();
            $table->decimal('reorder_cost', 10, 2)->nullable();
            $table->string('storage_location', 200)->nullable();

            $table->foreignId('supplier_id')->nullable()->constrained('parts_suppliers')->nullOnDelete();

            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();
            $table->foreignId('deleted_by')->nullable()->constrained('users')->nullOnDelete();

            $table->index(['organization_id', 'part_number']);
            $table->index(['garage_id', 'category']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('parts_inventory');
    }
};
