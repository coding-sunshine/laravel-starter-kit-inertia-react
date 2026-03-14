<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('column_management', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('table_name');
            $table->jsonb('columns');
            $table->bigInteger('legacy_id')->nullable()->unique();
            $table->timestamps();
            $table->unique(['user_id', 'table_name']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('column_management');
    }
};
