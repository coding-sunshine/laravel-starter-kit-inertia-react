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

            $table->date('loading_date')->nullable()->index();

            $table->integer('priority_number')->nullable()->index();

            $table->string('destination_code', 20)->nullable()->index();

            $table->decimal('under_load_mt', 12, 2)->nullable();

            $table->decimal('over_load_mt', 12, 2)->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('rakes', function (Blueprint $table) {

            $table->dropColumn([
                'loading_date',
                'priority_number',
                'destination_code',
                'under_load_mt',
                'over_load_mt',
            ]);
        });
    }
};
