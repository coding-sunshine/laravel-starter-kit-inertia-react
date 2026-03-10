<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasIndex('rakes', 'rakes_rake_number_unique')) {
            Schema::table('rakes', function (Blueprint $table) {
                $table->dropUnique('rakes_rake_number_unique');
            });
        }
    }

    public function down(): void
    {
        Schema::table('rakes', function (Blueprint $table) {
            $table->unique('rake_number');
        });
    }
};
