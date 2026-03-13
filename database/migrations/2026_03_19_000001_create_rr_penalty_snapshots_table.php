<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('rr_penalty_snapshots', function (Blueprint $table): void {
            $table->id();

            $table->foreignId('rr_document_id')
                ->constrained('rr_documents')
                ->cascadeOnDelete();

            $table->foreignId('rake_id')
                ->nullable()
                ->constrained('rakes')
                ->nullOnDelete();

            $table->string('penalty_code', 20);
            $table->decimal('amount', 12, 2);

            $table->string('wagon_number', 20)->nullable();
            $table->unsignedInteger('wagon_sequence')->nullable();

            $table->json('meta')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('rr_penalty_snapshots');
    }
};
