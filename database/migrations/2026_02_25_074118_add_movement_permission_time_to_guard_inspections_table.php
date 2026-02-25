<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('guard_inspections', function (Blueprint $table) {
            $table->dateTime('movement_permission_time')->nullable()->after('inspection_time');
        });
    }

    public function down(): void
    {
        Schema::table('guard_inspections', function (Blueprint $table) {
            $table->dropColumn('movement_permission_time');
        });
    }
};
