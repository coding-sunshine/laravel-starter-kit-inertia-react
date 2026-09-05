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
            $table->text('pakur_imwb_period')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('rakes', function (Blueprint $table) {
            $table->string('pakur_imwb_period', 50)->nullable(false)->change();
        });
    }
};
