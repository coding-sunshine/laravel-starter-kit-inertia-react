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
            $table->string('rake_number', 100)->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('rakes', function (Blueprint $table): void {
            $table->string('rake_number', 20)->nullable()->change();
        });
    }
};
