<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('projects', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('legacy_project_id')->nullable()->unique();
            $table->foreignId('organization_id')->nullable()->constrained()->nullOnDelete();
            $table->string('title');
            $table->string('stage')->nullable();
            $table->string('estate');
            $table->unsignedInteger('total_lots')->nullable();
            $table->unsignedInteger('storeys')->nullable();
            $table->unsignedInteger('min_landsize')->nullable();
            $table->unsignedInteger('max_landsize')->nullable();
            $table->unsignedInteger('min_living_area')->nullable();
            $table->unsignedInteger('max_living_area')->nullable();
            $table->string('bedrooms', 20)->nullable();
            $table->string('bathrooms', 20)->nullable();
            $table->string('min_bedrooms')->nullable();
            $table->string('max_bedrooms')->nullable();
            $table->string('min_bathrooms')->nullable();
            $table->string('max_bathrooms')->nullable();
            $table->unsignedInteger('garage')->nullable();
            $table->decimal('min_rent', 8, 2)->unsigned()->nullable();
            $table->decimal('max_rent', 8, 2)->unsigned()->nullable();
            $table->decimal('avg_rent', 8, 2)->unsigned()->nullable();
            $table->decimal('min_rent_yield', 8, 2)->unsigned()->nullable();
            $table->decimal('max_rent_yield', 8, 2)->unsigned()->nullable();
            $table->decimal('avg_rent_yield', 8, 2)->unsigned()->nullable();
            $table->decimal('rent_to_sell_yield', 8, 2)->unsigned()->nullable();
            $table->boolean('is_hot_property')->default(false);
            $table->text('description')->nullable();
            $table->decimal('min_price', 10, 2)->unsigned()->nullable();
            $table->decimal('max_price', 10, 2)->unsigned()->nullable();
            $table->decimal('avg_price', 10, 2)->unsigned()->nullable();
            $table->unsignedInteger('body_corporate_fees')->nullable();
            $table->unsignedInteger('min_body_corporate_fees')->nullable();
            $table->unsignedInteger('max_body_corporate_fees')->nullable();
            $table->unsignedInteger('rates_fees')->nullable();
            $table->unsignedInteger('min_rates_fees')->nullable();
            $table->unsignedInteger('max_rates_fees')->nullable();
            $table->decimal('sub_agent_comms', 8, 2)->nullable();
            $table->boolean('is_archived')->default(false);
            $table->boolean('is_hidden')->default(false);
            $table->date('start_at')->nullable();
            $table->date('end_at')->nullable();
            $table->boolean('is_smsf')->default(true);
            $table->boolean('is_firb')->default(true);
            $table->boolean('is_ndis')->default(true);
            $table->boolean('is_cashflow_positive')->default(true);
            $table->string('build_time', 40)->nullable();
            $table->decimal('historical_growth', 8, 2)->unsigned()->nullable();
            $table->string('land_info', 50)->nullable();
            $table->foreignId('developer_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('projecttype_id')->nullable()->constrained()->nullOnDelete();
            $table->boolean('is_featured')->default(false);
            $table->json('trust_details')->nullable();
            $table->text('property_conditions')->nullable();
            $table->boolean('is_co_living')->default(false);
            $table->boolean('is_rooming')->default(false);
            $table->boolean('is_rent_to_sell')->default(false);
            $table->boolean('is_flexi')->default(false);
            $table->boolean('is_exclusive')->default(false);
            $table->timestamps();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();

            $table->index('organization_id');
            $table->index('stage');
            $table->index(['is_archived', 'is_exclusive', 'is_hidden']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('projects');
    }
};
