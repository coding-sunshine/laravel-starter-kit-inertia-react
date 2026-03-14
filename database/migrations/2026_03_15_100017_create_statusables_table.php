<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('statusables', function (Blueprint $table) {
            $table->id();
            $table->foreignId('crm_status_id')->constrained('crm_statuses')->cascadeOnDelete();
            $table->nullableMorphs('statusable');
            $table->bigInteger('legacy_id')->nullable()->unique();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('statusables');
    }
};
