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
        Schema::create('sync_queue', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained('users')->onDelete('cascade');
            $table->string('action'); // create, update, delete
            $table->string('model_type'); // e.g., "VehicleUnload", "Wagon", "Indent"
            $table->string('model_id')->nullable(); // ID of the model being synced
            $table->json('payload'); // The form data to sync
            $table->string('status')->default('pending'); // pending, syncing, synced, failed
            $table->integer('retry_count')->default(0);
            $table->timestamp('last_attempted_at')->nullable();
            $table->text('error_message')->nullable();
            $table->timestamps();

            $table->index(['status', 'created_at']);
            $table->index('user_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sync_queue');
    }
};
