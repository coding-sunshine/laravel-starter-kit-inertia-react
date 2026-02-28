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
            $table->unsignedInteger('token_count')->nullable()->after('chunk_index');
        });
    }

    public function down(): void
    {
        Schema::table('document_chunks', function (Blueprint $table): void {
            $table->dropColumn('token_count');
        });
    }
};
