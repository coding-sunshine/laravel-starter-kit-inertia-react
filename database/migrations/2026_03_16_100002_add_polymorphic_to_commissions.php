<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('commissions', function (Blueprint $table): void {
            $table->unsignedBigInteger('legacy_id')->nullable()->index()->after('id');
            $table->string('commissionable_type')->nullable()->after('sale_id');
            $table->unsignedBigInteger('commissionable_id')->nullable()->after('commissionable_type');

            $table->index(['commissionable_type', 'commissionable_id']);
        });

        // Make sale_id nullable for legacy commissions that don't have a sale
        Schema::table('commissions', function (Blueprint $table): void {
            $table->dropForeign(['sale_id']);
            $table->unsignedBigInteger('sale_id')->nullable()->change();
            $table->foreign('sale_id')->references('id')->on('sales')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('commissions', function (Blueprint $table): void {
            $table->dropForeign(['sale_id']);
            $table->unsignedBigInteger('sale_id')->nullable(false)->change();
            $table->foreign('sale_id')->references('id')->on('sales')->cascadeOnDelete();
        });

        Schema::table('commissions', function (Blueprint $table): void {
            $table->dropIndex(['commissionable_type', 'commissionable_id']);
            $table->dropColumn(['legacy_id', 'commissionable_type', 'commissionable_id']);
        });
    }
};
