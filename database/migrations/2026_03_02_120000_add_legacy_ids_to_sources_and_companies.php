<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sources', function (Blueprint $table): void {
            $table->unsignedBigInteger('legacy_source_id')->nullable()->unique()->after('id');
        });
        Schema::table('companies', function (Blueprint $table): void {
            $table->unsignedBigInteger('legacy_company_id')->nullable()->unique()->after('id');
        });
    }

    public function down(): void
    {
        Schema::table('sources', function (Blueprint $table): void {
            $table->dropColumn('legacy_source_id');
        });
        Schema::table('companies', function (Blueprint $table): void {
            $table->dropColumn('legacy_company_id');
        });
    }
};
