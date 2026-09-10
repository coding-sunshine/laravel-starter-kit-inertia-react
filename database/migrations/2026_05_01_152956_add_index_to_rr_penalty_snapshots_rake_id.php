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
            $table->index('rake_id', 'rr_penalty_snapshots_rake_id_index');
        });
    }

    public function down(): void
    {
        Schema::table('rr_penalty_snapshots', function (Blueprint $table): void {
            $table->dropIndex('rr_penalty_snapshots_rake_id_index');
        });
    }
};
