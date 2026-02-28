<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('workshop_bays', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignId('garage_id')->constrained();

            $table->string('name', 200);
            $table->string('code', 50)->nullable();
            $table->string('status', 20)->default('available'); // available, occupied, maintenance, reserved

            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true);

            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();
            $table->foreignId('deleted_by')->nullable()->constrained('users')->nullOnDelete();

            $table->index(['organization_id', 'garage_id']);
            $table->index(['garage_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('workshop_bays');
    }
};
