<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('contact_attributions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('contact_id')->constrained()->cascadeOnDelete();
            $table->unsignedBigInteger('campaign_id')->nullable()->index();
            $table->string('campaign_name')->nullable();
            $table->string('ad_id')->nullable();
            $table->string('ad_name')->nullable();
            $table->foreignId('attributed_agent_contact_id')->nullable()->constrained('contacts')->nullOnDelete();
            $table->string('source')->nullable();
            $table->timestamp('attributed_at');
            $table->unsignedBigInteger('organization_id')->nullable()->index();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('contact_attributions');
    }
};
