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
        Schema::create('vehicle_unload_steps', function (Blueprint $table) {
            $table->id();

            $table->foreignId('vehicle_unload_id')
                ->constrained('vehicle_unloads')
                ->onDelete('cascade');

            $table->unsignedTinyInteger('step_number');

            $table->string('status')->default('PENDING');

            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();

            $table->text('remarks')->nullable();

            $table->foreignId('updated_by')
                ->nullable()
                ->constrained('users')
                ->onDelete('set null');

            $table->timestamps();

            $table->unique(['vehicle_unload_id', 'step_number']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('vehicle_unload_steps');
    }
};
