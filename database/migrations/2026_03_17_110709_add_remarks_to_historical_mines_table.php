<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('historical_mines', function (Blueprint $table): void {
            $table->text('remarks')->nullable()->after('ob_production_qty');
        });
    }

    public function down(): void
    {
        Schema::table('historical_mines', function (Blueprint $table): void {
            $table->dropColumn('remarks');
        });
    }
};
