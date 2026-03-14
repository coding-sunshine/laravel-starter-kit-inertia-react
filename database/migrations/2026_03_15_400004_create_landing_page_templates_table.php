<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('landing_page_templates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('campaign_website_id')->nullable()->constrained()->nullOnDelete();
            $table->string('name');
            $table->string('slug')->unique();
            $table->text('description')->nullable();
            $table->string('headline')->nullable();
            $table->text('sub_headline')->nullable();
            $table->longText('html_content')->nullable();
            $table->jsonb('puck_content')->nullable();
            $table->boolean('puck_enabled')->default(false);
            $table->string('status')->default('draft'); // draft, published, archived
            $table->string('meta_title')->nullable();
            $table->text('meta_description')->nullable();
            $table->jsonb('seo_config')->nullable();
            $table->boolean('is_active')->default(false);
            $table->userstamps();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('landing_page_templates');
    }
};
