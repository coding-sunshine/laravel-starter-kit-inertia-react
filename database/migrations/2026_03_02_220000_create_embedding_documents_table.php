<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Throwable;

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
            if (str_contains($e->getMessage(), 'permission denied') || str_contains($e->getMessage(), '42501')) {
                return;
            }
            throw $e;
        }

        Schema::create('embedding_documents', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('organization_id')->nullable()->constrained()->nullOnDelete();
            $table->string('document_type', 64);
            $table->string('document_id', 64);
            $table->text('content');
            $table->vector('embedding', 1536)->nullable();
            $table->timestampsTz();

            $table->unique(['organization_id', 'document_type', 'document_id']);
        });
    }

    public function down(): void
    {
        if (Schema::getConnection()->getDriverName() !== 'pgsql') {
            return;
        }

        Schema::dropIfExists('embedding_documents');
    }
};
