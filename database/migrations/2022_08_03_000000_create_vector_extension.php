<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::getConnection()->getDriverName() !== 'pgsql') {
            return;
        }

        try {
            DB::statement('CREATE EXTENSION IF NOT EXISTS vector');
        } catch (Throwable $e) {
            // Managed Postgres (e.g. Laravel Cloud) may not allow CREATE EXTENSION for the app user.
            // Skip when extension is not installed (e.g. pgvector not installed locally).
            // Skip so deploy succeeds; enable the vector extension at the infrastructure level if needed.
            $msg = $e->getMessage();
            if (str_contains($msg, 'permission denied') || str_contains($msg, '42501')
                || str_contains($msg, 'is not available') || str_contains($msg, 'Feature not supported')) {
                return;
            }
            throw $e;
        }
    }

    public function down(): void
    {
        if (Schema::getConnection()->getDriverName() !== 'pgsql') {
            return;
        }

        try {
            DB::statement('DROP EXTENSION IF EXISTS vector');
        } catch (Throwable $e) {
            if (str_contains($e->getMessage(), 'permission denied') || str_contains($e->getMessage(), '42501')) {
                return;
            }
            throw $e;
        }
    }
};
