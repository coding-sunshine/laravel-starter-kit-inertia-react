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
            $table->index(
                ['data_source', 'siding_id', 'loading_date'],
                'rakes_data_source_siding_id_loading_date_index'
            );
        });
    }

    public function down(): void
    {
        Schema::table('rakes', function (Blueprint $table): void {
            $table->dropIndex('rakes_data_source_siding_id_loading_date_index');
        });
    }
};
