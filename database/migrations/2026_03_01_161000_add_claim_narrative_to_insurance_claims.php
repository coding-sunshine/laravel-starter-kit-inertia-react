<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('insurance_claims', function (Blueprint $table): void {
            $table->text('claim_narrative')->nullable()->after('legal_representative');
        });
    }

    public function down(): void
    {
        Schema::table('insurance_claims', function (Blueprint $table): void {
            $table->dropColumn('claim_narrative');
        });
    }
};
