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
        Schema::table('rakes', function (Blueprint $table) {

            $table->foreignId('indent_id')
                ->nullable()
                ->constrained('indents')
                ->cascadeOnDelete()
                ->comment('Nullable for backward compatibility with existing staging data. New records should have indent_id.');

            // Enforce 1:1 relationship only for non-null values
            // PostgreSQL treats NULL as equal in unique constraints, so use partial unique index
            $table->unique(['indent_id'])->whereNotNull('indent_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('rakes', function (Blueprint $table) {
            $table->dropUnique(['indent_id']);
            $table->dropForeign(['indent_id']);
            $table->dropColumn('indent_id');
        });
    }
};
