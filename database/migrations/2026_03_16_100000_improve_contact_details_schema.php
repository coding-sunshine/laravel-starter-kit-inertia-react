<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('contact_emails', function (Blueprint $table): void {
            $table->softDeletes();
            $table->unique(['contact_id', 'value']);
        });

        Schema::table('contact_phones', function (Blueprint $table): void {
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::table('contact_emails', function (Blueprint $table): void {
            $table->dropSoftDeletes();
            $table->dropUnique(['contact_id', 'value']);
        });

        Schema::table('contact_phones', function (Blueprint $table): void {
            $table->dropSoftDeletes();
        });
    }
};
