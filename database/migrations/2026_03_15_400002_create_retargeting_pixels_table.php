<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('retargeting_pixels', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->nullable()->constrained()->nullOnDelete();
            $table->string('name');
            $table->string('platform'); // facebook, google, tiktok, linkedin, twitter
            $table->string('pixel_id');
            $table->text('script_tag')->nullable();
            $table->string('status')->default('active'); // active, paused
            $table->jsonb('events')->nullable(); // tracked events config
            $table->userstamps();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('retargeting_pixels');
    }
};
