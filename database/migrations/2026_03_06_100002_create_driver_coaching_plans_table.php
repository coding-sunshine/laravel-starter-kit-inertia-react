<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('driver_coaching_plans', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignId('driver_id')->constrained();

            $table->string('plan_type', 50); // safety, performance, induction, refresher
            $table->string('title', 200)->nullable();
            $table->text('objectives')->nullable();
            $table->json('objectives_json')->nullable();

            $table->string('status', 20)->default('draft'); // draft, active, completed, cancelled
            $table->date('due_date')->nullable();
            $table->date('completed_at')->nullable();

            $table->foreignId('assigned_coach_id')->nullable()->constrained('users')->nullOnDelete();
            $table->text('notes')->nullable();

            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();
            $table->foreignId('deleted_by')->nullable()->constrained('users')->nullOnDelete();

            $table->index(['organization_id', 'status']);
            $table->index(['driver_id', 'status']);
            $table->index(['due_date', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('driver_coaching_plans');
    }
};
