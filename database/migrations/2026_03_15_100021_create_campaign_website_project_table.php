<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('campaign_website_project', function (Blueprint $table) {
            $table->id();
            $table->foreignId('campaign_website_id')->constrained('campaign_websites')->cascadeOnDelete();
            $table->foreignId('project_id')->constrained('projects')->cascadeOnDelete();
            $table->timestamps();
            $table->unique(['campaign_website_id', 'project_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('campaign_website_project');
    }
};
