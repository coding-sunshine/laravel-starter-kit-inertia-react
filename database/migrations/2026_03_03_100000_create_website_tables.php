<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('websites', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('organization_id')->nullable()->constrained()->nullOnDelete();
            $table->string('name');
            $table->string('domain')->nullable();
            $table->json('settings')->nullable();
            $table->timestampsTz();
            $table->softDeletesTz();
        });

        Schema::create('website_pages', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('website_id')->constrained()->cascadeOnDelete();
            $table->string('title');
            $table->string('slug');
            $table->longText('content')->nullable();
            $table->unsignedInteger('order')->default(0);
            $table->timestampsTz();
        });

        Schema::create('website_elements', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('website_page_id')->constrained()->cascadeOnDelete();
            $table->string('type');
            $table->json('config')->nullable();
            $table->unsignedInteger('order')->default(0);
            $table->timestampsTz();
        });

        Schema::create('wordpress_websites', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('organization_id')->nullable()->constrained()->nullOnDelete();
            $table->string('name');
            $table->string('url')->nullable();
            $table->string('api_key')->nullable();
            $table->timestampsTz();
        });

        Schema::create('wordpress_templates', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->json('schema')->nullable();
            $table->timestampsTz();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('wordpress_templates');
        Schema::dropIfExists('wordpress_websites');
        Schema::dropIfExists('website_elements');
        Schema::dropIfExists('website_pages');
        Schema::dropIfExists('websites');
    }
};
