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
        Schema::table('rake_weighments', function (Blueprint $table): void {
            $table->decimal('total_gross_weight_mt', 12, 2)->nullable()->after('priority_number');
            $table->decimal('total_tare_weight_mt', 12, 2)->nullable()->after('total_gross_weight_mt');
            $table->decimal('total_net_weight_mt', 12, 2)->nullable()->after('total_tare_weight_mt');
            $table->decimal('total_cc_weight_mt', 12, 2)->nullable()->after('total_net_weight_mt');
            $table->decimal('total_under_load_mt', 12, 2)->nullable()->after('total_cc_weight_mt');
            $table->decimal('total_over_load_mt', 12, 2)->nullable()->after('total_under_load_mt');
            $table->decimal('maximum_train_speed_kmph', 8, 2)->nullable()->after('total_over_load_mt');
            $table->decimal('maximum_weight_mt', 12, 2)->nullable()->after('maximum_train_speed_kmph');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('rake_weighments', function (Blueprint $table): void {
            $table->dropColumn([
                'total_gross_weight_mt',
                'total_tare_weight_mt',
                'total_net_weight_mt',
                'total_cc_weight_mt',
                'total_under_load_mt',
                'total_over_load_mt',
                'maximum_train_speed_kmph',
                'maximum_weight_mt',
            ]);
        });
    }
};
