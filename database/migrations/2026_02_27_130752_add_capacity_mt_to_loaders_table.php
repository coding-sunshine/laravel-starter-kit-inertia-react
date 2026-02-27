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
        Schema::table('loaders', function (Blueprint $table): void {
            $table->decimal('capacity_mt', 8, 2)->nullable()->after('loader_type'); // capacity in metric tons
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('loaders', function (Blueprint $table): void {
            $table->dropColumn('capacity_mt');
        });
    }
};
