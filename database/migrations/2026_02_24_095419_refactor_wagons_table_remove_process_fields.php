<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('wagons', function (Blueprint $table) {

            $table->dropForeign(['loader_id']);
            $table->dropColumn([
                'loader_id',
                'is_overloaded',
            ]);
        });
    }

    public function down(): void
    {
        Schema::table('wagons', function (Blueprint $table) {

            $table->foreignId('loader_id')
                ->nullable()
                ->constrained('loaders')
                ->onDelete('set null');
            $table->boolean('is_overloaded')->default(false);
        });
    }
};
