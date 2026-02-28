<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('driver_working_time', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignId('driver_id')->constrained()->cascadeOnDelete();

            $table->date('date');

            $table->timestamp('shift_start_time')->nullable();
            $table->timestamp('shift_end_time')->nullable();

            $table->unsignedSmallInteger('break_time_minutes')->default(0);
            $table->unsignedSmallInteger('driving_time_minutes')->default(0);
            $table->unsignedSmallInteger('other_work_time_minutes')->default(0);
            $table->unsignedSmallInteger('available_time_minutes')->default(0);
            $table->unsignedSmallInteger('rest_time_minutes')->default(0);
            $table->unsignedSmallInteger('total_duty_time_minutes')->default(0);

            $table->unsignedSmallInteger('weekly_driving_time_minutes')->default(0);
            $table->unsignedSmallInteger('fortnightly_driving_time_minutes')->default(0);

            $table->boolean('wtd_compliant')->default(true);
            $table->boolean('rtd_compliant')->default(true);
            $table->json('violations')->nullable();

            $table->json('tachograph_data')->nullable();
            $table->boolean('manual_entry')->default(false);
            $table->foreignId('verified_by')->nullable()->constrained('users')->nullOnDelete();

            $table->text('notes')->nullable();

            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();
            $table->foreignId('deleted_by')->nullable()->constrained('users')->nullOnDelete();

            $table->unique(['driver_id', 'date']);
            $table->index(['driver_id', 'date']);
            $table->index(['wtd_compliant', 'rtd_compliant', 'date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('driver_working_time');
    }
};
