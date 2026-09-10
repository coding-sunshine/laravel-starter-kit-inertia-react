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
            $table->string('rr_number', 50)->nullable()->index();
        });
    }

    public function down(): void
    {
        Schema::table('rakes', function (Blueprint $table) {
            $table->dropColumn('rr_number');
        });
    }
};
