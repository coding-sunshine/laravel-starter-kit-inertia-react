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
        Schema::table('siding_user', function (Blueprint $table): void {
            $table->boolean('is_primary')->default(false)->after('siding_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('siding_user', function (Blueprint $table): void {
            $table->dropColumn('is_primary');
        });
    }
};
