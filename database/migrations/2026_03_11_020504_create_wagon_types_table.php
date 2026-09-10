<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('wagon_types', function (Blueprint $table): void {
            $table->id();
            $table->string('code', 20)->unique();
            $table->string('full_form')->nullable();
            $table->string('typical_use')->nullable();
            $table->string('loading_method')->nullable();
            $table->decimal('carrying_capacity_min_mt', 10, 2);
            $table->decimal('carrying_capacity_max_mt', 10, 2);
            $table->decimal('gross_tare_weight_mt', 10, 2);
            $table->decimal('default_pcc_weight_mt', 10, 2)->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('wagon_types');
    }
};
