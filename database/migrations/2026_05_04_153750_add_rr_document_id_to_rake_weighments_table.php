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
        Schema::table('rake_weighments', function (Blueprint $table): void {
            $table->foreignId('rr_document_id')
                ->nullable()
                ->after('rake_id')
                ->constrained('rr_documents')
                ->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('rake_weighments', function (Blueprint $table): void {
            $table->dropForeign(['rr_document_id']);
        });
    }
};
