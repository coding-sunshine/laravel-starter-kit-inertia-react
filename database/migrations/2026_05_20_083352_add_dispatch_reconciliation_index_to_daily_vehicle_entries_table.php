<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('daily_vehicle_entries', function (Blueprint $table): void {
            $table->index(
                ['siding_id', 'entry_type', 'entry_date', 'shift'],
                'dve_siding_type_date_shift_index',
            );
        });
    }

    public function down(): void
    {
        Schema::table('daily_vehicle_entries', function (Blueprint $table): void {
            $table->dropIndex('dve_siding_type_date_shift_index');
        });
    }
};
