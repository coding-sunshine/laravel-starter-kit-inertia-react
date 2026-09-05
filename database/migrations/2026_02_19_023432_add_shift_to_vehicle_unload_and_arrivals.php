<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Shift-wise coal receipt entry (SOW 4.2): morning, evening, night.
     */
    public function up(): void
    {
        Schema::table('vehicle_unloads', function (Blueprint $table): void {
            $table->string('shift', 20)->nullable()->after('arrival_time');
        });
        Schema::table('vehicle_arrivals', function (Blueprint $table): void {
            $table->string('shift', 20)->nullable()->after('arrived_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('vehicle_unloads', function (Blueprint $table): void {
            $table->dropColumn('shift');
        });
        Schema::table('vehicle_arrivals', function (Blueprint $table): void {
            $table->dropColumn('shift');
        });
    }
};
