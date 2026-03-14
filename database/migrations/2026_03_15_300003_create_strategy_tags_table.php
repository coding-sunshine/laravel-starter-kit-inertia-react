<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('strategy_tags', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('organization_id')->nullable()->constrained('organizations')->nullOnDelete();
            $table->string('name');
            $table->string('type');
            $table->string('color')->default('#6366f1');
            $table->timestamps();
        });

        Schema::create('contact_strategy_tag', function (Blueprint $table): void {
            $table->foreignId('contact_id')->constrained('contacts')->cascadeOnDelete();
            $table->foreignId('strategy_tag_id')->constrained('strategy_tags')->cascadeOnDelete();
            $table->timestamp('created_at')->nullable();

            $table->primary(['contact_id', 'strategy_tag_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('contact_strategy_tag');
        Schema::dropIfExists('strategy_tags');
    }
};
