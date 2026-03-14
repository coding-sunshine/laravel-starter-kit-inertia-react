<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('survey_questions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('questionnaire_id')->nullable()->constrained('questionnaires')->nullOnDelete();
            $table->string('question');
            $table->string('type')->default('text');
            $table->jsonb('options')->nullable();
            $table->integer('order')->default(0);
            $table->boolean('is_required')->default(false);
            $table->bigInteger('legacy_id')->nullable()->unique();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('survey_questions');
    }
};
