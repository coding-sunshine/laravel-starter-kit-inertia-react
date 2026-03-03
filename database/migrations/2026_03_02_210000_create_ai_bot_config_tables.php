<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ai_bot_categories', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('organization_id')->nullable()->constrained()->nullOnDelete();
            $table->string('name');
            $table->string('slug')->nullable();
            $table->integer('order_column')->nullable();
            $table->timestampsTz();
        });

        Schema::create('ai_bot_prompt_commands', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('organization_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('ai_bot_category_id')->nullable()->constrained('ai_bot_categories')->nullOnDelete();
            $table->string('name');
            $table->string('slug')->nullable();
            $table->text('prompt')->nullable();
            $table->text('description')->nullable();
            $table->string('type')->nullable();
            $table->boolean('is_active')->default(true);
            $table->integer('order_column')->nullable();
            $table->timestampsTz();
        });

        Schema::create('ai_bot_boxes', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('organization_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('ai_bot_category_id')->nullable()->constrained('ai_bot_categories')->nullOnDelete();
            $table->string('title');
            $table->text('description')->nullable();
            $table->text('page_overview')->nullable();
            $table->string('type')->nullable();
            $table->string('visibility')->nullable();
            $table->string('status')->nullable();
            $table->integer('order_column')->nullable();
            $table->timestampsTz();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ai_bot_boxes');
        Schema::dropIfExists('ai_bot_prompt_commands');
        Schema::dropIfExists('ai_bot_categories');
    }
};
