<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('loadrite_downtime_events', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('siding_id')->constrained('sidings')->cascadeOnDelete();
            $table->string('downtime_id', 64)->comment('Loadrite-side primary key');
            $table->dateTime('start_local_time');
            $table->dateTime('end_local_time')->nullable();
            $table->unsignedInteger('duration_minutes')->nullable();
            $table->string('reason_name', 128)->nullable();
            $table->string('sub_reason_name', 128)->nullable();
            $table->string('equipment_name', 128)->nullable();
            $table->json('raw_payload');
            $table->timestamps();

            $table->unique(['siding_id', 'downtime_id']);
            $table->index(['siding_id', 'start_local_time']);
            $table->index(['siding_id', 'end_local_time']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('loadrite_downtime_events');
    }
};
