<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('defects', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignId('vehicle_id')->constrained();

            $table->string('defect_number', 50);
            $table->string('title', 200);
            $table->text('description');

            $table->string('category', 50); // safety, mechanical, electrical, bodywork, other
            $table->string('severity', 50); // minor, major, dangerous
            $table->string('priority', 50)->default('medium'); // low, medium, high, urgent

            $table->foreignId('reported_by_driver_id')->nullable()->constrained('drivers')->nullOnDelete();
            $table->foreignId('reported_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('reported_at');
            $table->string('location_on_vehicle', 200)->nullable();

            $table->unsignedTinyInteger('images_count')->default(0);

            $table->string('status', 50)->default('reported'); // reported, acknowledged, assigned, work_ordered, in_progress, resolved, cannot_reproduce
            $table->foreignId('assigned_to')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('work_order_id')->nullable()->constrained('work_orders')->nullOnDelete();

            $table->decimal('estimated_cost', 8, 2)->nullable();
            $table->decimal('actual_cost', 8, 2)->nullable();

            $table->boolean('affects_roadworthiness')->default(false);
            $table->boolean('affects_safety')->default(false);
            $table->boolean('vehicle_off_road_required')->default(false);

            $table->boolean('temporary_fix_applied')->default(false);
            $table->text('temporary_fix_description')->nullable();

            $table->timestamp('resolution_date')->nullable();
            $table->text('resolution_description')->nullable();
            $table->text('root_cause_analysis')->nullable();
            $table->text('preventive_measures')->nullable();

            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();
            $table->foreignId('deleted_by')->nullable()->constrained('users')->nullOnDelete();

            $table->unique(['organization_id', 'defect_number']);
            $table->index(['vehicle_id', 'status', 'severity']);
            $table->index(['reported_at', 'status']);
            $table->index(['severity', 'priority', 'affects_roadworthiness']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('defects');
    }
};
