<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('rr_documents', function (Blueprint $table) {
            $table->string('class', 30)->nullable()->after('commodity_description');
            $table->decimal('rate', 12, 2)->nullable()->after('class');
        });
    }

    public function down(): void
    {
        Schema::table('rr_documents', function (Blueprint $table) {
            $table->dropColumn(['class', 'rate']);
        });
    }
};
