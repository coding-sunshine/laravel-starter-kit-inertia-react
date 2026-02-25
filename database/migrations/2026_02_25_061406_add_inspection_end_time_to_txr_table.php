<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('txr', function (Blueprint $table): void {
            $table->dateTime('inspection_end_time')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('txr', function (Blueprint $table): void {
            $table->dropColumn('inspection_end_time');
        });
    }
};
