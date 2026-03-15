<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sync_state', function (Blueprint $table): void {
            $table->id();
            $table->string('direction', 32)->comment('mysql_to_pgsql or pgsql_to_mysql');
            $table->string('table_name', 64);
            $table->timestamp('last_synced_at')->nullable();
            $table->unsignedBigInteger('last_synced_id')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->unique(['direction', 'table_name']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sync_state');
    }
};
