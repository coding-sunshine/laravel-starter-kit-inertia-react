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
        Schema::create('rake_charges', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('rake_id')->constrained('rakes')->cascadeOnDelete();
            $table->enum('charge_type', [
                'FREIGHT',
                'OTHER_CHARGE',
                'PENALTY',
                'GST',
                'REBATE',
            ]);
            $table->decimal('amount', 12, 2);
            $table->string('data_source')->nullable();
            $table->boolean('is_actual_charges')->default(false);
            $table->text('remarks')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('rake_charges');
    }
};
