<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('fines', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignId('vehicle_id')->constrained();
            $table->foreignId('driver_id')->nullable()->constrained()->nullOnDelete();

            $table->string('fine_type', 50); // speeding, parking, other
            $table->text('offence_description')->nullable();
            $table->date('offence_date');
            $table->decimal('amount', 10, 2);
            $table->decimal('amount_paid', 10, 2)->default(0);
            $table->date('due_date')->nullable();
            $table->date('appeal_deadline')->nullable();
            $table->string('status', 20)->default('pending'); // pending, paid, appealed, waived
            $table->text('appeal_notes')->nullable();
            $table->string('external_reference', 100)->nullable();
            $table->string('issuing_authority', 200)->nullable();

            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();
            $table->foreignId('deleted_by')->nullable()->constrained('users')->nullOnDelete();

            $table->index(['organization_id', 'status']);
            $table->index(['vehicle_id', 'offence_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('fines');
    }
};
