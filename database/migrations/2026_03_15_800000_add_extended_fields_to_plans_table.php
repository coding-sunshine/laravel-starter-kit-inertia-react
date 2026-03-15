<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('plans', function (Blueprint $table): void {
            if (! Schema::hasColumn('plans', 'setup_fee')) {
                $table->decimal('setup_fee', 10, 2)->default(0)->after('price');
            }
            if (! Schema::hasColumn('plans', 'is_public')) {
                $table->boolean('is_public')->default(true)->after('setup_fee');
            }
            if (! Schema::hasColumn('plans', 'max_users')) {
                $table->unsignedInteger('max_users')->nullable()->after('is_public');
            }
            if (! Schema::hasColumn('plans', 'max_php_sites')) {
                $table->unsignedInteger('max_php_sites')->default(0)->after('max_users');
            }
            if (! Schema::hasColumn('plans', 'max_wp_sites')) {
                $table->unsignedInteger('max_wp_sites')->default(0)->after('max_php_sites');
            }
            if (! Schema::hasColumn('plans', 'features')) {
                $table->json('features')->nullable()->after('max_wp_sites');
            }
        });
    }

    public function down(): void
    {
        Schema::table('plans', function (Blueprint $table): void {
            foreach (['setup_fee', 'is_public', 'max_users', 'max_php_sites', 'max_wp_sites', 'features'] as $column) {
                if (Schema::hasColumn('plans', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
