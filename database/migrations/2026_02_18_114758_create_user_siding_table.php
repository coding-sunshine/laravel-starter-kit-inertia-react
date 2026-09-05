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
        Schema::create('user_siding', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('siding_id')->constrained('sidings')->onDelete('cascade');
            $table->boolean('is_primary')->default(false); // Primary siding for auto-redirect
            $table->timestamps();

            $table->unique(['user_id', 'siding_id']);
            $table->index('user_id');
            $table->index('siding_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('user_siding');
    }
};
