<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('siding_vehicle_dispatches', function (Blueprint $table): void {
            $table->index(['siding_id', 'issued_on'], 'svd_siding_issued_on_index');
        });

        Schema::table('dispatch_reports', function (Blueprint $table): void {
            $table->index(['siding_id', 'issued_on'], 'dispatch_reports_siding_issued_on_index');
        });

        if (Schema::getConnection()->getDriverName() === 'pgsql') {
            DB::statement('CREATE INDEX svd_siding_issued_date_index ON siding_vehicle_dispatches (siding_id, (issued_on::date))');
        }
    }

    public function down(): void
    {
        if (Schema::getConnection()->getDriverName() === 'pgsql') {
            DB::statement('DROP INDEX IF EXISTS svd_siding_issued_date_index');
        }

        Schema::table('dispatch_reports', function (Blueprint $table): void {
            $table->dropIndex('dispatch_reports_siding_issued_on_index');
        });

        Schema::table('siding_vehicle_dispatches', function (Blueprint $table): void {
            $table->dropIndex('svd_siding_issued_on_index');
        });
    }
};
