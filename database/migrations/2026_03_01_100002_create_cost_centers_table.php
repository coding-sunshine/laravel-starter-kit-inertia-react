<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cost_centers', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();

            $table->string('code', 50);
            $table->string('name', 200);
            $table->text('description')->nullable();

            $table->foreignId('parent_cost_center_id')->nullable()->constrained('cost_centers')->nullOnDelete();
            $table->string('cost_center_type', 50); // department, project, location, vehicle_type

            $table->foreignId('manager_user_id')->nullable()->constrained('users')->nullOnDelete();

            $table->decimal('budget_annual', 15, 2)->nullable();
            $table->decimal('budget_monthly', 12, 2)->nullable();

            $table->boolean('is_active')->default(true);

            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();
            $table->foreignId('deleted_by')->nullable()->constrained('users')->nullOnDelete();

            $table->unique(['organization_id', 'code']);
            $table->index(['organization_id', 'is_active']);
            $table->index(['parent_cost_center_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cost_centers');
    }
};
