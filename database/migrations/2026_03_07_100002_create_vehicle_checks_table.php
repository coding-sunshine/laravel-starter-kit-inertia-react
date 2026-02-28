<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('vehicle_checks', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignId('vehicle_id')->constrained();
            $table->foreignId('vehicle_check_template_id')->constrained();

            $table->foreignId('performed_by_driver_id')->nullable()->constrained('drivers')->nullOnDelete();
            $table->foreignId('performed_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('defect_id')->nullable()->constrained()->nullOnDelete();

            $table->date('check_date');
            $table->string('status', 20)->default('in_progress'); // in_progress, completed, failed

            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();
            $table->foreignId('deleted_by')->nullable()->constrained('users')->nullOnDelete();

            $table->index(['organization_id', 'check_date']);
            $table->index(['vehicle_id', 'check_date']);
            $table->index(['vehicle_check_template_id', 'check_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('vehicle_checks');
    }
};
