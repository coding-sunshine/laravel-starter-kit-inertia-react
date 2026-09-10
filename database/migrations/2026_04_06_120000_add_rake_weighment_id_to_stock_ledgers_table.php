<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('stock_ledgers', function (Blueprint $table): void {
            $table->foreignId('rake_weighment_id')
                ->nullable()
                ->after('daily_vehicle_entry_id')
                ->constrained('rake_weighments')
                ->nullOnDelete();

            $table->index('rake_weighment_id');
        });
    }

    public function down(): void
    {
        Schema::table('stock_ledgers', function (Blueprint $table): void {
            $table->dropForeign(['rake_weighment_id']);
        });
    }
};
