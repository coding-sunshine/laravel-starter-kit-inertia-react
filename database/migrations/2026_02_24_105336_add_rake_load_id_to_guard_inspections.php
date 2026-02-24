<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('guard_inspections', function (Blueprint $table) {

            $table->foreignId('rake_load_id')
                ->nullable()
                ->after('rake_id')
                ->constrained('rake_loads')
                ->cascadeOnDelete();

            $table->index('rake_load_id');
        });
    }

    public function down(): void
    {
        Schema::table('guard_inspections', function (Blueprint $table) {

            $table->dropForeign(['rake_load_id']);
            $table->dropIndex(['rake_load_id']);
            $table->dropColumn('rake_load_id');
        });
    }
};
