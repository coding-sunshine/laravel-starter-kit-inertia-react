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
        Schema::table('indents', function (Blueprint $table): void {
            $table->string('e_demand_reference_id', 100)->nullable()->after('remarks');
            $table->string('fnr_number', 50)->nullable()->after('e_demand_reference_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('indents', function (Blueprint $table): void {
            $table->dropColumn(['e_demand_reference_id', 'fnr_number']);
        });
    }
};
