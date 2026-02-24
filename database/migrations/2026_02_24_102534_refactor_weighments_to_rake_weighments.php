<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1️⃣ Rename table
        Schema::rename('weighments', 'rake_weighments');

        // 2️⃣ Rename status column
        Schema::table('rake_weighments', function (Blueprint $table) {
            $table->renameColumn('weighment_status', 'status');
        });

        // 3️⃣ Modify structure
        Schema::table('rake_weighments', function (Blueprint $table) {

            // Add attempt tracking
            $table->integer('attempt_no')->default(1);

            // Add rake_load reference (nullable for now)
            $table->foreignId('rake_load_id')
                ->nullable()
                ->constrained('rake_loads')
                ->cascadeOnDelete();

            // Add speed validation
            $table->decimal('train_speed_kmph', 5, 2);

            // Remove unnecessary column
            $table->dropColumn('average_wagon_weight_mt');

            // Add indexes
            $table->index(['rake_id', 'attempt_no'], 'rake_attempt_index');
            $table->index('rake_load_id');
        });

        // 4️⃣ Update default for status
        Schema::table('rake_weighments', function (Blueprint $table) {
            $table->string('status')->default('pending')->change();
        });
    }

    public function down(): void
    {
        Schema::table('rake_weighments', function (Blueprint $table) {

            $table->dropIndex('rake_attempt_index');
            $table->dropIndex(['rake_load_id']);

            $table->dropForeign(['rake_load_id']);
            $table->dropColumn([
                'attempt_no',
                'train_speed_kmph',
                'rake_load_id',
            ]);

            $table->decimal('average_wagon_weight_mt', 12, 2)->nullable();
        });

        Schema::table('rake_weighments', function (Blueprint $table) {
            $table->renameColumn('status', 'weighment_status');
        });

        Schema::rename('rake_weighments', 'weighments');
    }
};
