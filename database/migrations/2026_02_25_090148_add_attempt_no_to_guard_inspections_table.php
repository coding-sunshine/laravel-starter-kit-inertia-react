<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('guard_inspections', function (Blueprint $table) {
            $table->integer('attempt_no')->default(1)->after('rake_load_id');
            $table->index(['rake_load_id', 'attempt_no']);
        });
    }

    public function down(): void
    {
        Schema::table('guard_inspections', function (Blueprint $table) {
            $table->dropIndex(['rake_load_id', 'attempt_no']);
            $table->dropColumn('attempt_no');
        });
    }
};
