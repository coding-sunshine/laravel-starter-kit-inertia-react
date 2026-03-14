<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('wordpress_websites', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignId('wordpress_template_id')->nullable();
            $table->string('title');
            $table->string('url')->nullable();
            $table->string('type')->default('real_estate'); // real_estate | wealth_creation | finance
            $table->unsignedTinyInteger('stage')->default(1); // 1=Pending 2=Initializing 3=Active 4=Removing
            $table->unsignedTinyInteger('step')->default(0);
            $table->boolean('is_custom_url')->default(false);
            $table->boolean('is_verified_url')->default(false);
            $table->string('instance_id')->nullable();
            $table->string('url_key')->nullable();
            $table->string('wp_username')->nullable();
            $table->string('wp_password')->nullable();
            $table->jsonb('enquiry_recipient_emails')->nullable();
            $table->string('primary_color')->nullable();
            $table->string('secondary_color')->nullable();
            $table->string('primary_text_color')->nullable();
            $table->boolean('is_enabled')->default(true);
            $table->integer('legacy_id')->nullable()->unique();
            $table->timestamps();
            $table->softDeletes();

            $table->index('organization_id');
            $table->index('stage');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('wordpress_websites');
    }
};
