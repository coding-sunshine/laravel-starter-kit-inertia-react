<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ai_job_runs', function (Blueprint $table): void {
            $table->string('laravel_job_id', 100)->nullable()->after('result_data');
        });
    }

    public function down(): void
    {
        Schema::table('ai_job_runs', function (Blueprint $table): void {
            $table->dropColumn('laravel_job_id');
        });
    }
};
