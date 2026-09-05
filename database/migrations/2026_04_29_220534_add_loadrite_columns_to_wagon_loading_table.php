<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('wagon_loading', function (Blueprint $table): void {
            $table->decimal('loadrite_weight_mt', 8, 3)->nullable()->after('loaded_quantity_mt');
            $table->enum('weight_source', ['manual', 'loadrite', 'weighbridge'])->default('manual')->after('loadrite_weight_mt');
            $table->dateTime('loadrite_last_synced_at')->nullable()->after('weight_source');
            $table->boolean('loadrite_override')->default(false)->after('loadrite_last_synced_at');
        });
    }

    public function down(): void
    {
        Schema::table('wagon_loading', function (Blueprint $table): void {
            $table->dropColumn(['loadrite_weight_mt', 'weight_source', 'loadrite_last_synced_at', 'loadrite_override']);
        });
    }
};
