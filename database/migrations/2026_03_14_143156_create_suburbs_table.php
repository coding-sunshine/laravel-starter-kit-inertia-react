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
        Schema::create('suburbs', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->string('postcode', 20)->nullable();
            $table->foreignId('state_id')->nullable()->constrained()->nullOnDelete();
            $table->decimal('lat', 10, 8)->nullable();
            $table->decimal('lng', 11, 8)->nullable();
            $table->bigInteger('legacy_id')->nullable()->index();
            $table->timestamps();

            $table->index(['name', 'postcode']);
            $table->index('state_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('suburbs');
    }
};
