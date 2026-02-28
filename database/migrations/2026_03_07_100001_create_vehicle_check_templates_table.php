<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('vehicle_check_templates', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();

            $table->string('name', 200);
            $table->string('code', 50)->nullable();
            $table->string('check_type', 50); // daily, weekly, pre_trip, post_trip, inspection
            $table->string('category', 100)->nullable();

            $table->json('checklist')->nullable(); // array of { label, result_type }
            $table->string('workflow_route', 200)->nullable();
            $table->unsignedTinyInteger('completion_percentage_threshold')->nullable();
            $table->boolean('is_active')->default(true);

            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();
            $table->foreignId('deleted_by')->nullable()->constrained('users')->nullOnDelete();

            $table->index(['organization_id', 'check_type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('vehicle_check_templates');
    }
};
