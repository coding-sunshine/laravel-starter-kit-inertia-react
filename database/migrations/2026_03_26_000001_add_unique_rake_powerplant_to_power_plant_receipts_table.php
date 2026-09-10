<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('power_plant_receipts', function (Blueprint $table): void {
            $table->unique(['rake_id', 'power_plant_id'], 'ppr_rake_power_plant_unique');
        });
    }

    public function down(): void
    {
        Schema::table('power_plant_receipts', function (Blueprint $table): void {
            $table->dropUnique('ppr_rake_power_plant_unique');
        });
    }
};
