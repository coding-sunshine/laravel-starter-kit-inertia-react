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
        Schema::create('indents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('siding_id')->constrained('sidings')->onDelete('cascade');
            $table->string('indent_number', 20)->unique();
            $table->decimal('target_quantity_mt', 12, 2);
            $table->decimal('allocated_quantity_mt', 12, 2)->default(0);
            $table->string('state')->default('pending'); // pending, allocated, partial, completed, cancelled
            $table->dateTime('indent_date');
            $table->dateTime('required_by_date')->nullable();
            $table->string('remarks')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->onDelete('set null');
            $table->foreignId('updated_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamps();
            $table->softDeletes();

            $table->index(['siding_id', 'state']);
            $table->index('indent_number');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('indents');
    }
};
