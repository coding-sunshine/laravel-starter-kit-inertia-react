<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('visibility_demos', function (Blueprint $table): void {
            $table->foreignId('organization_id')->nullable()->after('id')->constrained()->nullOnDelete();
            $table->string('visibility')->default('organization')->after('organization_id');
            $table->unsignedBigInteger('cloned_from')->nullable()->after('visibility');
            $table->string('title')->after('cloned_from');

            $table->index(['organization_id', 'visibility']);
        });
    }

    public function down(): void
    {
        Schema::table('visibility_demos', function (Blueprint $table): void {
            $table->dropIndex(['organization_id', 'visibility']);
            $table->dropForeign(['organization_id']);
            $table->dropColumn(['organization_id', 'visibility', 'cloned_from', 'title']);
        });
    }
};
