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
        Schema::create('lots', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('project_id')->constrained()->cascadeOnDelete();
            $table->bigInteger('legacy_id')->nullable()->index();
            $table->string('slug')->unique()->nullable();
            $table->string('title')->nullable();
            $table->decimal('land_price', 15, 2)->nullable();
            $table->decimal('build_price', 15, 2)->nullable();
            $table->string('stage')->nullable();
            $table->string('level')->nullable();
            $table->string('building')->nullable();
            $table->string('floorplan')->nullable();
            $table->integer('car')->nullable();
            $table->string('storage')->nullable();
            $table->string('view')->nullable();
            $table->integer('garage')->nullable();
            $table->string('aspect')->nullable();
            $table->decimal('internal', 10, 2)->nullable();
            $table->decimal('external', 10, 2)->nullable();
            $table->decimal('total', 10, 2)->nullable();
            $table->integer('storeys')->nullable();
            $table->decimal('land_size', 10, 2)->nullable();
            $table->string('title_status')->default('available')->index(); // available, reserved, sold
            $table->decimal('living_area', 10, 2)->nullable();
            $table->decimal('price', 15, 2)->nullable();
            $table->integer('bedrooms')->nullable();
            $table->integer('bathrooms')->nullable();
            $table->integer('study')->nullable();
            $table->boolean('mpr')->default(false);
            $table->boolean('powder_room')->default(false);
            $table->decimal('balcony', 10, 2)->nullable();
            $table->decimal('rent_yield', 5, 2)->nullable();
            $table->decimal('weekly_rent', 10, 2)->nullable();
            $table->decimal('rates', 10, 2)->nullable();
            $table->decimal('body_corporation', 10, 2)->nullable();
            $table->boolean('is_archived')->default(false)->index();
            $table->boolean('is_nras')->default(false);
            $table->boolean('is_smsf')->default(false);
            $table->boolean('is_cashflow_positive')->default(false);
            $table->date('completion')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index('project_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('lots');
    }
};
