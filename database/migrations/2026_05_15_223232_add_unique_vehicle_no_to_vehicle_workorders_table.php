<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Ensure duplicate non-null `vehicle_no` values are resolved before migrating; uniqueness allows multiple NULLs.
     */
    public function up(): void
    {
        Schema::table('vehicle_workorders', function (Blueprint $table): void {
            $table->dropIndex(['vehicle_no']);
            $table->unique('vehicle_no');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('vehicle_workorders', function (Blueprint $table): void {
            $table->dropUnique(['vehicle_no']);
            $table->index('vehicle_no');
        });
    }
};
