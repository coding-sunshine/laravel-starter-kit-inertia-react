<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('flyer_templates', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('legacy_flyer_template_id')->nullable()->unique();
            $table->foreignId('organization_id')->nullable()->constrained()->nullOnDelete();
            $table->string('template_id');
            $table->string('name');
            $table->string('preview_img')->nullable();
            $table->boolean('is_enabled')->default(true);
            $table->timestamps();

            $table->index('organization_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('flyer_templates');
    }
};
