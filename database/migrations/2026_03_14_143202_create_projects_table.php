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
        Schema::create('projects', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('organization_id')->nullable()->constrained()->nullOnDelete();
            $table->bigInteger('legacy_id')->nullable()->index();
            $table->string('slug')->unique()->nullable();
            $table->string('title');
            $table->string('stage')->default('selling')->index(); // pre_launch, selling, completed, archived
            $table->string('estate')->nullable();
            $table->integer('total_lots')->nullable();
            $table->integer('storeys')->nullable();
            $table->decimal('min_landsize', 10, 2)->nullable();
            $table->decimal('max_landsize', 10, 2)->nullable();
            $table->decimal('living_area', 10, 2)->nullable();
            $table->integer('bedrooms')->nullable();
            $table->integer('bathrooms')->nullable();
            $table->integer('garage')->nullable();
            $table->decimal('min_rent', 10, 2)->nullable();
            $table->decimal('max_rent', 10, 2)->nullable();
            $table->decimal('avg_rent', 10, 2)->nullable();
            $table->decimal('rent_yield', 5, 2)->nullable();
            $table->boolean('is_hot_property')->default(false)->index();
            $table->longText('description')->nullable();
            $table->text('description_summary')->nullable();
            $table->decimal('min_price', 15, 2)->nullable();
            $table->decimal('max_price', 15, 2)->nullable();
            $table->decimal('avg_price', 15, 2)->nullable();
            $table->decimal('body_corporate_fees', 10, 2)->nullable();
            $table->decimal('rates_fees', 10, 2)->nullable();
            $table->boolean('is_archived')->default(false)->index();
            $table->boolean('is_hidden')->default(false);
            $table->date('start_at')->nullable();
            $table->date('end_at')->nullable();
            $table->boolean('is_smsf')->default(false);
            $table->boolean('is_firb')->default(false);
            $table->boolean('is_ndis')->default(false);
            $table->boolean('is_cashflow_positive')->default(false);
            $table->string('build_time')->nullable();
            $table->decimal('historical_growth', 5, 2)->nullable();
            $table->text('land_info')->nullable();
            $table->foreignId('developer_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('projecttype_id')->nullable()->constrained()->nullOnDelete();
            $table->decimal('lat', 10, 8)->nullable();
            $table->decimal('lng', 11, 8)->nullable();
            $table->boolean('is_featured')->default(false)->index();
            $table->integer('featured_order')->nullable();
            $table->boolean('is_co_living')->default(false);
            $table->boolean('is_high_cap_growth')->default(false);
            $table->boolean('is_rooming')->default(false);
            $table->boolean('is_rent_to_sell')->default(false);
            $table->boolean('is_exclusive')->default(false);
            // suburb/state stored as strings for denormalization (fast search without joins)
            $table->string('suburb')->nullable()->index();
            $table->string('state')->nullable()->index();
            $table->string('postcode', 20)->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index('organization_id');
            $table->index(['lat', 'lng']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('projects');
    }
};
