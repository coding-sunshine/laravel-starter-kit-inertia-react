<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('rakes', function (Blueprint $table) {

            // -------------------------
            // REMOVE WRONG COLUMNS
            // -------------------------

            $table->dropColumn([
                'demurrage_hours',
                'demurrage_penalty_amount',
                'loading_start_time',
                'loading_end_time',
            ]);

            // -------------------------
            // ADD CORRECT PROCESS FIELDS
            // -------------------------

            $table->dateTime('placement_time')->nullable()
                ->comment('Time when rake is placed inside siding and free-time starts.');

            $table->dateTime('dispatch_time')->nullable()
                ->comment('Time when rake leaves siding after successful weighment.');
        });
    }

    public function down(): void
    {
        Schema::table('rakes', function (Blueprint $table) {

            // Restore removed columns (basic rollback safety)
            $table->integer('demurrage_hours')->default(0);
            $table->decimal('demurrage_penalty_amount', 12, 2)->default(0);
            $table->dateTime('loading_start_time')->nullable();
            $table->dateTime('loading_end_time')->nullable();

            $table->dropColumn([
                'placement_time',
                'dispatch_time',
            ]);
        });
    }
};
