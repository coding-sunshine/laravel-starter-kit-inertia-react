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
        Schema::create('txr', function (Blueprint $table) {
            $table->id();
            $table->foreignId('rake_id')->constrained('rakes')->onDelete('cascade');
            $table->dateTime('inspection_time');
            $table->string('state')->default('pending'); // pending, approved, rejected
            $table->integer('unfit_wagons_count')->default(0);
            $table->text('unfit_wagon_numbers')->nullable(); // JSON array or comma-separated
            $table->text('remarks')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamps();
            $table->softDeletes();

            $table->index(['rake_id', 'state']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('txr');
    }
};
