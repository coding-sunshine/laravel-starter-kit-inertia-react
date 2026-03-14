<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('match_scores', function (Blueprint $table) {
            $table->id();
            $table->foreignId('contact_id')->constrained()->cascadeOnDelete();
            $table->string('matchable_type'); // App\Models\Project or App\Models\Lot
            $table->unsignedBigInteger('matchable_id');
            $table->unsignedSmallInteger('score')->default(0); // 0-100
            $table->jsonb('factors')->nullable(); // breakdown of scoring factors
            $table->timestamp('computed_at')->useCurrent();

            $table->unique(['contact_id', 'matchable_type', 'matchable_id']);
            $table->index(['matchable_type', 'matchable_id']);
            $table->index(['contact_id', 'score']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('match_scores');
    }
};
