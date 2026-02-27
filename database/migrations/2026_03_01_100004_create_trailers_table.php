<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('trailers', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();

            $table->string('registration')->nullable();
            $table->string('fleet_number')->nullable();
            $table->string('type', 50); // flatbed, box, tank, refrigerated, lowloader, other
            $table->string('make', 100)->nullable();
            $table->string('model', 100)->nullable();
            $table->unsignedInteger('year')->nullable();

            $table->foreignId('home_location_id')->nullable()->constrained('locations')->nullOnDelete();

            $table->unsignedInteger('weight_kg')->nullable();
            $table->unsignedInteger('max_payload_kg')->nullable();

            $table->string('status', 50)->default('active'); // active, maintenance, vor, disposed
            $table->string('compliance_status', 50)->default('compliant'); // compliant, expiring_soon, expired
            $table->date('inspection_expiry_date')->nullable();

            $table->text('notes')->nullable();

            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();
            $table->foreignId('deleted_by')->nullable()->constrained('users')->nullOnDelete();

            $table->index(['organization_id', 'status']);
            $table->index(['home_location_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('trailers');
    }
};
