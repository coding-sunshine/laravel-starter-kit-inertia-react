<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('penalties', function (Blueprint $table) {
            $table->string('responsible_party', 30)->nullable()->after('penalty_status');
            $table->text('root_cause')->nullable()->after('responsible_party');
            $table->timestamp('disputed_at')->nullable()->after('remediation_notes');
            $table->text('dispute_reason')->nullable()->after('disputed_at');
            $table->timestamp('resolved_at')->nullable()->after('dispute_reason');
            $table->text('resolution_notes')->nullable()->after('resolved_at');

            $table->index('responsible_party');
        });
    }

    public function down(): void
    {
        Schema::table('penalties', function (Blueprint $table) {
            $table->dropIndex(['responsible_party']);
            $table->dropColumn([
                'responsible_party',
                'root_cause',
                'disputed_at',
                'dispute_reason',
                'resolved_at',
                'resolution_notes',
            ]);
        });
    }
};
