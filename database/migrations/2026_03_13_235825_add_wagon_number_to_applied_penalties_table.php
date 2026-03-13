<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('applied_penalties', function (Blueprint $table): void {
            $table->string('wagon_number')
                ->nullable()
                ->after('wagon_id');
        });
    }

    public function down(): void
    {
        Schema::table('applied_penalties', function (Blueprint $table): void {
            $table->dropColumn('wagon_number');
        });
    }
};
