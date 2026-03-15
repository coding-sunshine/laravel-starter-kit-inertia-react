<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('rr_penalty_snapshots', function (Blueprint $table): void {
            // Drop existing foreign key to change nullability
            $table->dropForeign(['rr_document_id']);

            // Make rr_document_id nullable
            $table->unsignedBigInteger('rr_document_id')->nullable()->change();

            // Re-add foreign key constraint (allows NULL, enforces when set)
            $table->foreign('rr_document_id')
                ->references('id')
                ->on('rr_documents')
                ->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('rr_penalty_snapshots', function (Blueprint $table): void {
            $table->dropForeign(['rr_document_id']);

            $table->unsignedBigInteger('rr_document_id')->nullable(false)->change();

            $table->foreign('rr_document_id')
                ->references('id')
                ->on('rr_documents')
                ->cascadeOnDelete();
        });
    }
};
