<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::getConnection()->getDriverName() !== 'pgsql') {
            return;
        }

        Schema::table('contact_embeddings', function (Blueprint $table): void {
            $table->dropColumn('embedding');
        });

        Schema::table('contact_embeddings', function (Blueprint $table): void {
            $table->vector('embedding', 1536)->nullable();
        });
    }

    public function down(): void
    {
        if (Schema::getConnection()->getDriverName() !== 'pgsql') {
            return;
        }

        Schema::table('contact_embeddings', function (Blueprint $table): void {
            $table->dropColumn('embedding');
        });

        Schema::table('contact_embeddings', function (Blueprint $table): void {
            $table->jsonb('embedding')->nullable();
        });
    }
};
