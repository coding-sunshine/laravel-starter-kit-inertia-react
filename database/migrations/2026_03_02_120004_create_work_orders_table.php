<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('work_orders', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignId('vehicle_id')->constrained();

            $table->string('work_order_number', 50);
            $table->string('title', 200);
            $table->text('description')->nullable();

            $table->string('work_type', 50); // service, repair, inspection, modification, recall
            $table->string('priority', 50)->default('medium'); // low, medium, high, urgent
            $table->string('status', 50)->default('draft'); // draft, pending, approved, in_progress, completed, cancelled
            $table->string('urgency', 50)->default('routine'); // routine, important, critical, emergency

            $table->foreignId('requested_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();

            $table->foreignId('assigned_garage_id')->nullable()->constrained('garages')->nullOnDelete();
            $table->string('assigned_technician', 200)->nullable();

            $table->date('scheduled_date')->nullable();
            $table->date('started_date')->nullable();
            $table->date('completed_date')->nullable();
            $table->date('due_date')->nullable();

            $table->decimal('estimated_hours', 5, 2)->nullable();
            $table->decimal('actual_hours', 5, 2)->nullable();
            $table->decimal('estimated_cost', 10, 2)->nullable();
            $table->decimal('parts_cost', 10, 2)->nullable();
            $table->decimal('labour_cost', 10, 2)->nullable();
            $table->decimal('total_cost', 10, 2)->nullable();

            $table->unsignedInteger('mileage_at_start')->nullable();
            $table->unsignedInteger('mileage_at_completion')->nullable();

            $table->boolean('vehicle_off_road')->default(false);
            $table->timestamp('vor_start_time')->nullable();
            $table->timestamp('vor_end_time')->nullable();

            $table->boolean('warranty_applicable')->default(false);
            $table->string('warranty_claim_number', 100)->nullable();
            $table->boolean('quality_check_passed')->nullable();
            $table->unsignedTinyInteger('customer_satisfaction_rating')->nullable();

            $table->text('completion_notes')->nullable();

            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();
            $table->foreignId('deleted_by')->nullable()->constrained('users')->nullOnDelete();

            $table->unique(['organization_id', 'work_order_number']);
            $table->index(['vehicle_id', 'status']);
            $table->index(['assigned_garage_id', 'status', 'scheduled_date']);
            $table->index(['due_date', 'status']);
            $table->index(['status', 'priority', 'urgency']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('work_orders');
    }
};
