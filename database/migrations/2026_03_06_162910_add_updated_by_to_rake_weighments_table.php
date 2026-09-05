<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('rake_weighments', function (Blueprint $table) {
            $table->foreignId('updated_by')->nullable()->after('created_by')->constrained('users');
        });
    }

    public function down(): void
    {
        Schema::table('rake_weighments', function (Blueprint $table) {
            $table->dropForeignIdFor(\App\Models\User::class, 'updated_by');
            $table->dropColumn('updated_by');
        });
    }
};
