<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('rake_wagon_weighments', function (Blueprint $table): void {
            // For Postgres this usually needs dropping/re-adding the constraint.
            // If `change()` works in your setup, you can do:
            // $table->foreignId('wagon_id')->nullable()->change();

            $table->dropForeign(['wagon_id']);
            $table->unsignedBigInteger('wagon_id')->nullable()->change();
            $table->foreign('wagon_id')
                ->references('id')
                ->on('wagons')
                ->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('rake_wagon_weighments', function (Blueprint $table): void {
            $table->dropForeign(['wagon_id']);
            $table->unsignedBigInteger('wagon_id')->nullable(false)->change();
            $table->foreign('wagon_id')
                ->references('id')
                ->on('wagons')
                ->cascadeOnDelete();
        });
    }
};
