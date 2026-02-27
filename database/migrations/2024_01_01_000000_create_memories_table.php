<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (Schema::getConnection()->getDriverName() !== 'pgsql') {
            return;
        }

        // Skip if pgvector extension is not installed (optional AI memory feature).
        $vectorInstalled = DB::selectOne("SELECT 1 FROM pg_extension WHERE extname = 'vector'");
        if (! $vectorInstalled) {
            return;
        }

        try {
            Schema::ensureVectorExtensionExists();
        } catch (Throwable $e) {
            if (str_contains($e->getMessage(), 'is not available') || str_contains($e->getMessage(), 'Feature not supported')) {
                return;
            }
            throw $e;
        }

        Schema::create('memories', function (Blueprint $table) {
            $table->id();
            $table->string('user_id')->nullable()->index();
            $table->text('content');
            $table->vector('embedding', dimensions: config('memory.dimensions'))->index();
            $table->timestamps();

            $table->index(['user_id', 'created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('memories');
    }
};
