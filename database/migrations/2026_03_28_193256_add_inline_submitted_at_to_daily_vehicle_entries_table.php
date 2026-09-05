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
        Schema::table('daily_vehicle_entries', function (Blueprint $table): void {
            $table->timestamp('inline_submitted_at')->nullable()->after('updated_at');
        });

        $driver = Schema::getConnection()->getDriverName();
        if ($driver === 'sqlite') {
            DB::statement(
                'UPDATE daily_vehicle_entries SET inline_submitted_at = COALESCE(updated_at, created_at) WHERE inline_submitted_at IS NULL'
            );
        } else {
            DB::table('daily_vehicle_entries')->update([
                'inline_submitted_at' => DB::raw('COALESCE(updated_at, created_at)'),
            ]);
        }
    }

    public function down(): void
    {
        Schema::table('daily_vehicle_entries', function (Blueprint $table): void {
            $table->dropColumn('inline_submitted_at');
        });
    }
};
