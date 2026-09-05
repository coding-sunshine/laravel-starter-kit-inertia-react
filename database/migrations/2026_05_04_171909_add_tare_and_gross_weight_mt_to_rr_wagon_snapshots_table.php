<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('rr_wagon_snapshots', function (Blueprint $table): void {
            $table->decimal('tare_weight_mt', 12, 2)->nullable()->after('overload_weight_mt');
            $table->decimal('gross_weight_mt', 12, 2)->nullable()->after('tare_weight_mt');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('rr_wagon_snapshots', function (Blueprint $table): void {
            $table->dropColumn(['tare_weight_mt', 'gross_weight_mt']);
        });
    }
};
