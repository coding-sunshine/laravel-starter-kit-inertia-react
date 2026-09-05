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
            $table
                ->string('loader_weighment_status', 30)
                ->nullable()
                ->after('weighment_end_time')
                ->index();
        });
    }

    public function down(): void
    {
        Schema::table('rakes', function (Blueprint $table): void {
            $table->dropIndex(['loader_weighment_status']);
            $table->dropColumn('loader_weighment_status');
        });
    }
};
