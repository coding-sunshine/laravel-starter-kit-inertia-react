<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            if (! Schema::hasColumn('users', 'created_via')) {
                $table->string('created_via')->default('auto')->after('onboarding_completed');
            }
            if (! Schema::hasColumn('users', 'eway_token_customer_id')) {
                $table->string('eway_token_customer_id')->nullable()->after('created_via');
            }
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            foreach (['created_via', 'eway_token_customer_id'] as $column) {
                if (Schema::hasColumn('users', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
