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
        Schema::table('rakes', function (Blueprint $table) {

            $table->integer('overload_wagon_count')
                ->nullable()
                ->after('over_load_mt');

        });
    }

    public function down(): void
    {
        Schema::table('rakes', function (Blueprint $table) {

            $table->dropColumn('overload_wagon_count');

        });
    }
};
