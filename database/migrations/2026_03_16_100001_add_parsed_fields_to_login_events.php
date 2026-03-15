<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('login_events', function (Blueprint $table): void {
            $table->string('browser_name')->nullable();
            $table->string('browser_version')->nullable();
            $table->string('os_name')->nullable();
            $table->string('os_version')->nullable();
            $table->string('device_type')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('login_events', function (Blueprint $table): void {
            $table->dropColumn([
                'browser_name',
                'browser_version',
                'os_name',
                'os_version',
                'device_type',
            ]);
        });
    }
};
