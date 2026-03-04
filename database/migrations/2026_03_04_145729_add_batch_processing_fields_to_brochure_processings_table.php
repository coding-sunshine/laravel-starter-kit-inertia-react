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
        Schema::table('brochure_processings', function (Blueprint $table) {
            $table->string('batch_id')->nullable()->after('organization_id');
            $table->enum('queue_status', ['pending', 'processing', 'completed', 'failed'])->default('pending')->after('status');
            $table->timestamp('processing_started_at')->nullable()->after('approved_at');
            $table->timestamp('processing_completed_at')->nullable()->after('processing_started_at');

            $table->index(['batch_id', 'queue_status']);
            $table->index(['organization_id', 'batch_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('brochure_processings', function (Blueprint $table) {
            $table->dropIndex(['batch_id', 'queue_status']);
            $table->dropIndex(['organization_id', 'batch_id']);

            $table->dropColumn([
                'batch_id',
                'queue_status',
                'processing_started_at',
                'processing_completed_at',
            ]);
        });
    }
};
