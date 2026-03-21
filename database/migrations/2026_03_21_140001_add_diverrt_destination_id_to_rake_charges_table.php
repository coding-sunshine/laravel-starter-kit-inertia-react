<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('rake_charges', function (Blueprint $table): void {
            $table->foreignId('diverrt_destination_id')
                ->nullable()
                ->constrained('diverrt_destination')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('rake_charges', function (Blueprint $table): void {
            $table->dropForeign(['diverrt_destination_id']);
            $table->dropColumn('diverrt_destination_id');
        });
    }
};
