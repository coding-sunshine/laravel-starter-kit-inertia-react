<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('parts_suppliers', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();

            $table->string('name', 200);
            $table->string('code', 50)->nullable();
            $table->string('contact_name', 200)->nullable();
            $table->string('contact_phone', 50)->nullable();
            $table->string('contact_email')->nullable();

            $table->text('address')->nullable();
            $table->string('postcode', 20)->nullable();
            $table->string('city', 100)->nullable();

            $table->string('payment_terms', 100)->nullable();
            $table->decimal('minimum_order_value', 10, 2)->nullable();
            $table->boolean('preferred')->default(false);
            $table->boolean('is_active')->default(true);

            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();
            $table->foreignId('deleted_by')->nullable()->constrained('users')->nullOnDelete();

            $table->index(['organization_id', 'is_active']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('parts_suppliers');
    }
};
