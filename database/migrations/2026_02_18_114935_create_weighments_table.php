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
        Schema::create('weighments', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('rake_id')->constrained('rakes')->onDelete('cascade');
            $table->dateTime('weighment_time');
            $table->decimal('total_weight_mt', 12, 2);
            $table->decimal('average_wagon_weight_mt', 12, 2)->nullable(); // Calculated in application layer
            $table->string('weighment_status')->default('recorded'); // recorded, verified
            $table->text('remarks')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamps();

            $table->index('rake_id');
            $table->index('weighment_time');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('weighments');
    }
};
