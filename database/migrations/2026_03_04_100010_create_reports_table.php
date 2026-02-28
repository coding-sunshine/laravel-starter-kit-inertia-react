<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('reports', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();

            $table->string('name', 200);
            $table->text('description')->nullable();

            $table->enum('report_type', [
                'fleet_utilization', 'fuel_efficiency', 'driver_performance',
                'maintenance_costs', 'compliance_status', 'safety_analysis',
                'cost_analysis', 'environmental_impact', 'custom',
            ]);

            $table->json('parameters')->nullable();
            $table->json('filters')->nullable();

            $table->boolean('schedule_enabled')->default(false);
            $table->enum('schedule_frequency', ['daily', 'weekly', 'monthly', 'quarterly'])->default('monthly');
            $table->unsignedTinyInteger('schedule_day_of_week')->nullable();
            $table->unsignedTinyInteger('schedule_day_of_month')->nullable();
            $table->date('next_run_date')->nullable();

            $table->json('recipients')->nullable();
            $table->enum('format', ['pdf', 'excel', 'csv', 'json'])->default('pdf');

            $table->boolean('is_active')->default(true);

            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();
            $table->foreignId('deleted_by')->nullable()->constrained('users')->nullOnDelete();

            $table->index(['organization_id', 'is_active', 'report_type']);
            $table->index(['schedule_enabled', 'next_run_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('reports');
    }
};
