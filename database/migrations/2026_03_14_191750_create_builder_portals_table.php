<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('builder_portals', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('logo_path')->nullable();
            $table->string('primary_color')->nullable();
            $table->string('secondary_color')->nullable();
            $table->string('contact_email')->nullable();
            $table->string('contact_phone')->nullable();
            $table->text('disclaimer')->nullable();
            $table->boolean('show_prices')->default(true);
            $table->boolean('show_agent_details')->default(true);
            $table->boolean('is_active')->default(true);
            $table->jsonb('allowed_project_ids')->nullable(); // null = all org projects
            $table->timestamps();
            $table->softDeletes();

            $table->index('organization_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('builder_portals');
    }
};
