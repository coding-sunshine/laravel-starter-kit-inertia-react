<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('rakes', function (Blueprint $table): void {
            $table->decimal('chargeable_weight', 12, 2)->nullable();
            $table->string('e_mining_chalan')->nullable();
            $table->string('weighment_place')->nullable();
            $table->dateTime('arrival_time')->nullable();
            $table->dateTime('drawn_out')->nullable();
            $table->decimal('out_ward_wt', 12, 2)->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('rakes', function (Blueprint $table): void {
            $table->dropColumn([
                'chargeable_weight',
                'e_mining_chalan',
                'weighment_place',
                'arrival_time',
                'drawn_out',
                'out_ward_wt',
            ]);
        });
    }
};
