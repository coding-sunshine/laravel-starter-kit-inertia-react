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
        Schema::table('rr_documents', function (Blueprint $table): void {
            $table->string('fnr', 50)->nullable()->after('rr_weight_mt');
            $table->string('from_station_code', 20)->nullable()->after('fnr');
            $table->string('to_station_code', 20)->nullable()->after('from_station_code');
            $table->decimal('freight_total', 14, 2)->nullable()->after('to_station_code');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('rr_documents', function (Blueprint $table): void {
            $table->dropColumn(['fnr', 'from_station_code', 'to_station_code', 'freight_total']);
        });
    }
};
