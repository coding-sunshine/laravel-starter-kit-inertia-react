<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('push_schedules', function (Blueprint $table) {
            $table->id();
            $table->string('pushable_type');
            $table->unsignedBigInteger('pushable_id');
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->string('channel'); // php, wordpress, rea, domain, homely
            $table->timestamp('scheduled_at');
            $table->timestamp('published_at')->nullable();
            $table->string('status')->default('pending'); // pending, published, failed, cancelled
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['pushable_type', 'pushable_id']);
            $table->index(['status', 'scheduled_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('push_schedules');
    }
};
