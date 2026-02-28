<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('vehicle_check_items', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('vehicle_check_id')->constrained()->cascadeOnDelete();

            $table->unsignedSmallInteger('item_index')->default(0);
            $table->string('label', 500);
            $table->string('result_type', 20); // pass_fail, value, photo
            $table->string('result', 50)->nullable(); // pass, fail, na
            $table->string('value_text', 500)->nullable();
            $table->unsignedBigInteger('photo_media_id')->nullable();
            $table->text('notes')->nullable();

            $table->timestamps();

            $table->index(['vehicle_check_id', 'item_index']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('vehicle_check_items');
    }
};
