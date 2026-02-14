<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create(config('level-up.table'), function (Blueprint $table): void {
            $table->id();
            $table->foreignId(config('level-up.user.foreign_key'))->constrained(config('level-up.user.users_table'));
            $table->foreignId('level_id')->constrained();
            $table->integer('experience_points')->default(0)->index();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists(config('level-up.table'));
    }
};
