<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('push_history', function (Blueprint $table) {
            $table->id();
            $table->string('pushable_type');
            $table->unsignedBigInteger('pushable_id');
            $table->index(['pushable_type', 'pushable_id']);
            $table->enum('channel', ['php', 'wordpress']);
            $table->timestamp('pushed_at');
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->json('response')->nullable();
            $table->string('status')->default('success');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('push_history');
    }
};
