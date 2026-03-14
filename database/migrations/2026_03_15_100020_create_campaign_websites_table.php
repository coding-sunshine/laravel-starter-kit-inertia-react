<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('campaign_websites', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignId('campaign_website_template_id')->nullable()->constrained('campaign_website_templates')->nullOnDelete();
            $table->uuid('site_id')->unique();
            $table->string('title');
            $table->string('short_link')->nullable();
            $table->boolean('is_multiple_property')->default(false);
            $table->boolean('is_custom_font')->default(false);
            $table->string('font_link')->nullable();
            $table->string('font_family')->nullable();
            $table->string('primary_color')->nullable();
            $table->string('secondary_color')->nullable();
            $table->jsonb('header')->nullable();
            $table->jsonb('banner')->nullable();
            $table->jsonb('page_content')->nullable();
            $table->jsonb('footer')->nullable();
            $table->jsonb('puck_content')->nullable();
            $table->boolean('puck_enabled')->default(false);
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->bigInteger('legacy_id')->nullable()->unique();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('campaign_websites');
    }
};
