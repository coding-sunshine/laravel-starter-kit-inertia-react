<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cold_outreach_templates', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('organization_id')->nullable()->constrained()->nullOnDelete();
            $table->string('name');
            $table->string('channel'); // email, sms
            $table->string('subject')->nullable();
            $table->text('body');
            $table->json('variants')->nullable(); // A/B variants
            $table->json('ctas')->nullable(); // call-to-action options
            $table->boolean('ai_generated')->default(false);
            $table->string('tone')->nullable(); // professional, friendly, urgent
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cold_outreach_templates');
    }
};
