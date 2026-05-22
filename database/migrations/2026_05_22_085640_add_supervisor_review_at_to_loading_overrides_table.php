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
        Schema::table('loading_overrides', function (Blueprint $table): void {
            $table->timestamp('supervisor_review_at')->nullable()->after('estimated_penalty_at_time');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('loading_overrides', function (Blueprint $table): void {
            $table->dropColumn('supervisor_review_at');
        });
    }
};
