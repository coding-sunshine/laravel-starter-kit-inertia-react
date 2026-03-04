<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('document_chunks', function (Blueprint $table): void {
            $table->string('source_type', 100)->nullable()->after('chunkable_id');
            $table->unsignedInteger('chunk_index')->nullable()->after('content');
        });
    }

    public function down(): void
    {
        Schema::table('document_chunks', function (Blueprint $table): void {
            $table->dropColumn(['source_type', 'chunk_index']);
        });
    }
};
