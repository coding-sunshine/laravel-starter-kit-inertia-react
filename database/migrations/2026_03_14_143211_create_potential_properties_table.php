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
        Schema::create('potential_properties', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('organization_id')->nullable()->constrained()->nullOnDelete();
            $table->string('title');
            $table->string('suburb')->nullable();
            $table->string('state')->nullable()->index();
            $table->string('developer_name')->nullable();
            $table->text('description')->nullable();
            $table->decimal('estimated_price_min', 15, 2)->nullable();
            $table->decimal('estimated_price_max', 15, 2)->nullable();
            $table->string('status')->default('evaluating')->index(); // evaluating, approved, rejected
            $table->boolean('imported_from_csv')->default(false);
            $table->json('csv_row_data')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index('organization_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('potential_properties');
    }
};
