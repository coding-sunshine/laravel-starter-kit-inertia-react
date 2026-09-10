<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('rakes', function (Blueprint $table) {

            $table->integer('detention_hours')->nullable()->after('overload_wagon_count');

            $table->integer('shunting_hours')->nullable()->after('detention_hours');

            $table->decimal('total_amount_rs', 12, 2)->nullable()->after('shunting_hours');

            $table->string('destination', 100)->nullable()->after('total_amount_rs');

            $table->string('pakur_imwb_period', 50)->nullable()->after('destination');

        });
    }

    public function down(): void
    {
        Schema::table('rakes', function (Blueprint $table) {

            $table->dropColumn([
                'detention_hours',
                'shunting_hours',
                'total_amount_rs',
                'destination',
                'pakur_imwb_period',
            ]);

        });
    }
};
