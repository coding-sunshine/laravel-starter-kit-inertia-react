<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sync_log', function (Blueprint $table): void {
            $table->id();
            $table->string('direction', 32)->comment('mysql_to_pgsql or pgsql_to_mysql');
            $table->string('table_name', 64);
            $table->string('row_key', 64)->nullable()->comment('Legacy id or business key');
            $table->string('status', 16)->default('success')->comment('success or failed');
            $table->text('message')->nullable();
            $table->timestamp('created_at')->useCurrent();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sync_log');
    }
};
