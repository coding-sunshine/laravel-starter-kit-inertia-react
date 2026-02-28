<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ppe_assignments', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('driver_id')->nullable()->constrained()->nullOnDelete();

            $table->string('ppe_type', 100);
            $table->string('item_reference', 100)->nullable();
            $table->date('issued_date');
            $table->date('expiry_or_return_date')->nullable();
            $table->string('status', 20)->default('active'); // active, returned, expired

            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();
            $table->foreignId('deleted_by')->nullable()->constrained('users')->nullOnDelete();

            $table->index(['organization_id', 'status']);
            $table->index(['driver_id', 'expiry_or_return_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ppe_assignments');
    }
};
