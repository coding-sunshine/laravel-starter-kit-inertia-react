<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('vehicle_discs', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignId('vehicle_id')->constrained();
            $table->foreignId('operator_licence_id')->constrained();

            $table->string('disc_number', 50);
            $table->date('valid_from');
            $table->date('valid_to');
            $table->string('status', 20)->default('active'); // active, expired, replaced

            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();
            $table->foreignId('deleted_by')->nullable()->constrained('users')->nullOnDelete();

            $table->index(['organization_id', 'vehicle_id']);
            $table->index(['valid_to', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('vehicle_discs');
    }
};
