<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('driver_wellness_records', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignId('driver_id')->constrained();

            $table->date('record_date');
            $table->unsignedTinyInteger('fatigue_level')->nullable(); // 1-5 scale
            $table->decimal('rest_hours', 4, 2)->nullable();
            $table->string('sleep_quality', 20)->nullable(); // poor, fair, good, excellent
            $table->string('mood', 20)->nullable();
            $table->text('notes')->nullable();

            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();
            $table->foreignId('deleted_by')->nullable()->constrained('users')->nullOnDelete();

            $table->index(['organization_id', 'record_date']);
            $table->index(['driver_id', 'record_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('driver_wellness_records');
    }
};
