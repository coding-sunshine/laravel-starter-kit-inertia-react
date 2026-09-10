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
        Schema::table('indents', function (Blueprint $table): void {
            $table->foreignId('deleted_by')->nullable()
                ->after('updated_by')
                ->constrained('users')
                ->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('indents', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('deleted_by');
        });
    }
};
