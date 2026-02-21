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
            $table->string('root_cause_category', 50)->nullable()->after('root_cause');
            $table->boolean('is_preventable')->nullable()->after('root_cause_category');
            $table->text('suggested_remediation')->nullable()->after('is_preventable');
        });
    }

    public function down(): void
    {
        Schema::table('penalties', function (Blueprint $table) {
            $table->dropColumn(['root_cause_category', 'is_preventable', 'suggested_remediation']);
        });
    }
};
