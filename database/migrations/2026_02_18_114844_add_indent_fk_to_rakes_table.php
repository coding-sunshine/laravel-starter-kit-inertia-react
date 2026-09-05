<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('rakes', function (Blueprint $table): void {
            if (! Schema::hasColumn('rakes', 'indent_id')) {
                $table->foreignId('indent_id')
                    ->nullable()
                    ->index()
                    ->constrained('indents')
                    ->cascadeOnDelete();

                return;
            }

            $table->foreign('indent_id')
                ->references('id')
                ->on('indents')
                ->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('rakes', function (Blueprint $table): void {
            $table->dropForeign(['indent_id']);
        });
    }
};
