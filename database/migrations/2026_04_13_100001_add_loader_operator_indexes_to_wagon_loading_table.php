<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Indexes for filtering and dashboard queries on loader operator name (see ExecutiveDashboardController::buildLoaderOverloadTrends).
     */
    public function up(): void
    {
        Schema::table('wagon_loading', function (Blueprint $table): void {
            $table->index(['loader_id', 'loader_operator_name'], 'wagon_loading_loader_id_loader_operator_name_index');
        });

        $driver = Schema::getConnection()->getDriverName();
        if ($driver === 'pgsql') {
            DB::statement('CREATE INDEX wagon_loading_loader_operator_name_not_null_index ON wagon_loading (loader_operator_name) WHERE loader_operator_name IS NOT NULL');
        } else {
            Schema::table('wagon_loading', function (Blueprint $table): void {
                $table->index('loader_operator_name');
            });
        }
    }

    public function down(): void
    {
        $driver = Schema::getConnection()->getDriverName();
        if ($driver === 'pgsql') {
            DB::statement('DROP INDEX IF EXISTS wagon_loading_loader_operator_name_not_null_index');
        } else {
            Schema::table('wagon_loading', function (Blueprint $table): void {
                $table->dropIndex(['loader_operator_name']);
            });
        }

        Schema::table('wagon_loading', function (Blueprint $table): void {
            $table->dropIndex('wagon_loading_loader_id_loader_operator_name_index');
        });
    }
};
