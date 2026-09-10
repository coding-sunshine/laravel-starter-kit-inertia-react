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
        Schema::table('rakes', function (Blueprint $table): void {
            $table->string('invoice_no')->nullable()->after('rr_number');
            $table->date('invoice_date')->nullable()->after('invoice_no');
            $table->boolean('is_diverted')->default(false)->after('invoice_date');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('rakes', function (Blueprint $table): void {
            $table->dropColumn([
                'invoice_no',
                'invoice_date',
                'is_diverted',
            ]);
        });
    }
};
