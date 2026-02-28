<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('document_chunks', function (Blueprint $table): void {
            $table->id();

            $table->string('chunkable_type');
            $table->unsignedBigInteger('chunkable_id');
            $table->index(['chunkable_type', 'chunkable_id']);

            $table->text('content');
            $table->json('metadata')->nullable();
            // Embedding: pgvector uses vector type; MySQL/SQLite store as json or skip. Omit vector column for portability.
            $table->json('embedding')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('document_chunks');
    }
};
