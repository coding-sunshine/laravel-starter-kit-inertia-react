<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('flyers', function (Blueprint $table) {
            $table->jsonb('puck_content')->nullable()->after('html_content');
            $table->boolean('puck_enabled')->default(false)->after('puck_content');
            $table->string('thumbnail_path')->nullable()->after('puck_enabled');
        });
    }

    public function down(): void
    {
        Schema::table('flyers', function (Blueprint $table) {
            $table->dropColumn(['puck_content', 'puck_enabled', 'thumbnail_path']);
        });
    }
};
