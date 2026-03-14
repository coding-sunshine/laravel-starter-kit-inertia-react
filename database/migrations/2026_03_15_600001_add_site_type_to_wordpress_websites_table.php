<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('wordpress_websites', function (Blueprint $table): void {
            $table->string('site_type')->default('wp_real_estate')->after('type');
        });
    }

    public function down(): void
    {
        Schema::table('wordpress_websites', function (Blueprint $table): void {
            $table->dropColumn('site_type');
        });
    }
};
