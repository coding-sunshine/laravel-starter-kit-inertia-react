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
        Schema::create('rr_documents', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('rake_id')->constrained('rakes')->onDelete('cascade');
            $table->string('rr_number', 50)->unique();
            $table->dateTime('rr_received_date');
            $table->decimal('rr_weight_mt', 12, 2)->nullable();
            $table->text('rr_details')->nullable(); // JSON or text from OCR
            $table->string('document_status')->default('received'); // received, verified, discrepancy
            $table->boolean('has_discrepancy')->default(false);
            $table->text('discrepancy_details')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamps();

            $table->index('rake_id');
            $table->index('rr_number');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('rr_documents');
    }
};
