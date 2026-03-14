<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ad_templates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->nullable()->constrained()->nullOnDelete();
            $table->string('name');
            $table->string('channel')->default('facebook'); // facebook, instagram, twitter, linkedin, google
            $table->string('type')->default('ad'); // ad, social, carousel, story
            $table->string('tone')->default('professional'); // professional, casual, urgent, friendly
            $table->text('headline')->nullable();
            $table->text('body_copy')->nullable();
            $table->string('cta_text')->nullable();
            $table->string('image_url')->nullable();
            $table->jsonb('metadata')->nullable();
            $table->boolean('is_active')->default(true);
            $table->userstamps();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ad_templates');
    }
};
