<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('lots', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('legacy_lot_id')->nullable()->unique();
            $table->foreignId('project_id')->constrained()->cascadeOnDelete();
            $table->string('title')->nullable();
            $table->decimal('land_price', 10, 2)->unsigned()->nullable();
            $table->decimal('build_price', 10, 2)->unsigned()->nullable();
            $table->string('stage')->nullable();
            $table->string('level', 100)->nullable();
            $table->string('building')->nullable();
            $table->string('floorplan')->nullable();
            $table->unsignedInteger('car')->nullable();
            $table->integer('storage')->nullable();
            $table->string('view')->nullable();
            $table->string('garage', 50)->nullable();
            $table->string('aspect')->nullable();
            $table->decimal('internal', 8, 2)->unsigned()->nullable();
            $table->decimal('external', 8, 2)->unsigned()->nullable();
            $table->unsignedInteger('total')->nullable();
            $table->unsignedInteger('storyes')->nullable();
            $table->string('land_size', 40)->nullable();
            $table->string('title_status')->nullable();
            $table->decimal('living_area', 8, 2)->unsigned()->nullable();
            $table->decimal('price', 10, 2)->unsigned()->nullable();
            $table->string('bedrooms', 40)->nullable();
            $table->string('bathrooms', 40)->nullable();
            $table->unsignedInteger('study')->nullable();
            $table->unsignedInteger('mpr')->nullable();
            $table->unsignedInteger('powder_room')->nullable();
            $table->unsignedInteger('balcony')->nullable();
            $table->decimal('rent_yield', 8, 2)->unsigned()->nullable();
            $table->decimal('weekly_rent', 8, 2)->unsigned()->nullable();
            $table->decimal('rent_to_sell_yield', 8, 2)->unsigned()->nullable();
            $table->unsignedInteger('rates')->nullable();
            $table->decimal('five_percent_share_price', 10, 2)->nullable();
            $table->decimal('sub_agent_comms', 8, 2)->nullable();
            $table->unsignedInteger('body_corporation')->nullable();
            $table->boolean('is_archived')->default(false);
            $table->boolean('is_nras')->default(false);
            $table->boolean('is_smsf')->default(false);
            $table->boolean('is_cashflow_positive')->default(false);
            $table->string('completion')->nullable();
            $table->string('uuid')->nullable();
            $table->timestamps();
            $table->softDeletes();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();

            $table->index('project_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('lots');
    }
};
