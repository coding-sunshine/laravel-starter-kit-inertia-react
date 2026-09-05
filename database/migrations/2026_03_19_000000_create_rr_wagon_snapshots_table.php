<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('rr_wagon_snapshots', function (Blueprint $table): void {
            $table->id();

            $table->foreignId('rr_document_id')
                ->constrained('rr_documents')
                ->cascadeOnDelete();

            $table->foreignId('rake_id')
                ->nullable()
                ->constrained('rakes')
                ->nullOnDelete();

            $table->unsignedInteger('wagon_sequence')->nullable();
            $table->string('wagon_number', 20)->nullable();
            $table->string('wagon_type', 50)->nullable();

            $table->decimal('pcc_weight_mt', 12, 2)->nullable();
            $table->decimal('loaded_weight_mt', 12, 2)->nullable();
            $table->decimal('permissible_weight_mt', 12, 2)->nullable();
            $table->decimal('overload_weight_mt', 12, 2)->nullable();

            $table->json('meta')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('rr_wagon_snapshots');
    }
};
