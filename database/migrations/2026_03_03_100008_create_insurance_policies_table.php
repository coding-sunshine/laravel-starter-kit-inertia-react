<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('insurance_policies', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();

            $table->string('policy_number', 100)->unique();
            $table->string('insurer_name', 200);
            $table->enum('policy_type', ['comprehensive', 'third_party', 'fleet', 'goods_in_transit']);
            $table->enum('coverage_type', ['individual', 'fleet', 'any_driver']);

            $table->date('start_date');
            $table->date('end_date');
            $table->decimal('premium_amount', 12, 2)->nullable();
            $table->decimal('excess_amount', 10, 2)->nullable();
            $table->unsignedTinyInteger('no_claims_bonus_years')->default(0);

            $table->json('covered_vehicles')->nullable();
            $table->json('covered_drivers')->nullable();
            $table->json('coverage_limits')->nullable();
            $table->json('exclusions')->nullable();

            $table->string('broker_name', 200)->nullable();
            $table->string('broker_contact', 200)->nullable();
            $table->json('policy_documents')->nullable();

            $table->boolean('auto_renewal')->default(false);
            $table->enum('status', ['active', 'expired', 'cancelled', 'pending'])->default('active');

            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();
            $table->foreignId('deleted_by')->nullable()->constrained('users')->nullOnDelete();

            $table->index(['organization_id', 'status']);
            $table->index(['start_date', 'end_date', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('insurance_policies');
    }
};
