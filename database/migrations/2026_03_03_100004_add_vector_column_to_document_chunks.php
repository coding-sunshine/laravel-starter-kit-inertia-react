<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('document_chunks', function (Blueprint $table): void {
            // Add a float array column for vector embeddings (professional approach)
            // This allows for efficient storage and indexing without requiring pgvector extension
            $table->json('embedding_vector')->nullable()->after('embedding');
        });

        // Migrate existing JSON embeddings to the new vector column
        Illuminate\Support\Facades\DB::statement('
            UPDATE document_chunks
            SET embedding_vector = embedding
            WHERE embedding IS NOT NULL
        ');

        // Note: GIN index on json is not supported in PostgreSQL (only jsonb). Vector similarity
        // is handled by a later migration (pgvector). Skip GIN here to avoid undefined operator class.
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('document_chunks', function (Blueprint $table): void {
            $table->dropColumn('embedding_vector');
        });
    }
};
