<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('txr', function (Blueprint $table) {

            $table->dropColumn([
                'unfit_wagons_count',
                'unfit_wagon_numbers',
            ]);

            $table->renameColumn('state', 'status');
        });
    }

    public function down(): void
    {
        Schema::table('txr', function (Blueprint $table) {

            $table->integer('unfit_wagons_count')->default(0);
            $table->text('unfit_wagon_numbers')->nullable();

            $table->renameColumn('status', 'state');
        });
    }
};
