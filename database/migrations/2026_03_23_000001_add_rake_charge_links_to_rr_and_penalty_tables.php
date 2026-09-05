<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('rr_charges', function (Blueprint $table): void {
            $table->foreignId('rake_charge_id')
                ->nullable()
                ->after('rr_document_id')
                ->constrained('rake_charges')
                ->nullOnDelete();
        });

        Schema::table('rr_penalty_snapshots', function (Blueprint $table): void {
            $table->foreignId('rake_charge_id')
                ->nullable()
                ->after('rake_id')
                ->constrained('rake_charges')
                ->nullOnDelete();
        });

        Schema::table('applied_penalties', function (Blueprint $table): void {
            $table->foreignId('rake_charge_id')
                ->nullable()
                ->after('rake_id')
                ->constrained('rake_charges')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('applied_penalties', function (Blueprint $table): void {
            $table->dropForeign(['rake_charge_id']);
            $table->dropColumn('rake_charge_id');
        });

        Schema::table('rr_penalty_snapshots', function (Blueprint $table): void {
            $table->dropForeign(['rake_charge_id']);
            $table->dropColumn('rake_charge_id');
        });

        Schema::table('rr_charges', function (Blueprint $table): void {
            $table->dropForeign(['rake_charge_id']);
            $table->dropColumn('rake_charge_id');
        });
    }
};
