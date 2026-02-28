<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('toolbox_talks', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignId('presenter_id')->nullable()->constrained('users')->nullOnDelete();

            $table->string('topic', 200);
            $table->text('content')->nullable();
            $table->date('scheduled_date')->nullable();
            $table->time('scheduled_time')->nullable();
            $table->string('location', 200)->nullable();

            $table->json('attendee_driver_ids')->nullable();
            $table->json('attendee_user_ids')->nullable();
            $table->unsignedInteger('attendance_count')->default(0);

            $table->string('status', 20)->default('scheduled'); // scheduled, completed, cancelled

            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();
            $table->foreignId('deleted_by')->nullable()->constrained('users')->nullOnDelete();

            $table->index(['organization_id', 'scheduled_date']);
            $table->index(['organization_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('toolbox_talks');
    }
};
