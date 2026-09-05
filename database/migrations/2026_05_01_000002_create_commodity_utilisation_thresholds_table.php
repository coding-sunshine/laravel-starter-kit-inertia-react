<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('commodity_utilisation_thresholds', function (Blueprint $table): void {
            $table->id();
            $table->string('commodity_grade', 64);
            $table->decimal('utilisation_threshold', 4, 3);
            $table->dateTime('effective_from');
            $table->dateTime('effective_to')->nullable();
            $table->string('source', 128)->nullable();
            $table->timestamps();
            $table->userstamps();

            $table->index(['commodity_grade', 'effective_from']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('commodity_utilisation_thresholds');
    }
};
