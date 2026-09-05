<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('production_entries', function (Blueprint $table): void {
            $table->string('trip')->nullable()->change();
            $table->decimal('qty', 12, 2)->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('production_entries', function (Blueprint $table): void {
            $table->string('trip')->nullable(false)->change();
            $table->decimal('qty', 12, 2)->nullable(false)->change();
        });
    }
};
