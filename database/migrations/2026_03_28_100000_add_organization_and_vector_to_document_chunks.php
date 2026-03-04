<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('document_chunks', function (Blueprint $table): void {
            $table->foreignId('organization_id')->nullable()->after('id')->constrained()->nullOnDelete();
        });

        if (Schema::getConnection()->getDriverName() !== 'pgsql') {
            return;
        }

        try {
            $installed = DB::selectOne("SELECT 1 FROM pg_extension WHERE extname = 'vector'");
        } catch (Throwable) {
            return;
        }

        if (! $installed) {
            return;
        }

        if (Schema::hasColumn('document_chunks', 'embedding_vector')) {
            Schema::table('document_chunks', function (Blueprint $table): void {
                $table->dropColumn('embedding_vector');
            });
        }

        Schema::table('document_chunks', function (Blueprint $table): void {
            $table->vector('embedding_vector', 1536)->nullable()->after('embedding');
        });
        // Optional: add ivfflat index for faster RAG once you have enough rows:
        // CREATE INDEX document_chunks_embedding_vector_idx ON document_chunks USING ivfflat (embedding_vector vector_cosine_ops) WITH (lists = 100);
    }

    public function down(): void
    {
        if (Schema::getConnection()->getDriverName() === 'pgsql' && Schema::hasColumn('document_chunks', 'embedding_vector')) {
            Schema::table('document_chunks', function (Blueprint $table): void {
                $table->dropColumn('embedding_vector');
            });
        }

        Schema::table('document_chunks', function (Blueprint $table): void {
            $table->dropForeign(['organization_id']);
        });
    }
};
