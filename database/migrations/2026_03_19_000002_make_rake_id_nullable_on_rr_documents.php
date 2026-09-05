<?php

declare(strict_types=1);
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('rr_documents', 'rake_id')) {
            return;
        }

        Schema::table('rr_documents', function (Blueprint $table): void {
            // Drop existing foreign key only if the column exists
            $table->dropForeign(['rake_id']);

            // Make column nullable
            $table->unsignedBigInteger('rake_id')->nullable()->change();

            // Re-add foreign key with nullOnDelete
            $table->foreign('rake_id')
                ->references('id')
                ->on('rakes')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        if (! Schema::hasColumn('rr_documents', 'rake_id')) {
            return;
        }

        Schema::table('rr_documents', function (Blueprint $table): void {
            $table->dropForeign(['rake_id']);

            $table->unsignedBigInteger('rake_id')->nullable(false)->change();

            $table->foreign('rake_id')
                ->references('id')
                ->on('rakes')
                ->onDelete('cascade');
        });
    }
};
