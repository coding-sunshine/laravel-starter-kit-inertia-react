<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('suburbs', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('legacy_suburb_id')->nullable()->unique();
            $table->foreignId('organization_id')->nullable()->constrained()->nullOnDelete();
            $table->string('postcode', 5);
            $table->string('suburb', 100);
            $table->string('state', 4);
            $table->double('latitude', 9, 6);
            $table->double('longitude', 9, 6);
            $table->integer('au_town_id')->nullable();
            $table->timestamps();

            $table->index('organization_id');
            $table->index(['state', 'postcode']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('suburbs');
    }
};
