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
        Schema::create('flyers', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('flyer_template_id')->nullable()->index();
            $table->unsignedBigInteger('project_id')->nullable()->index();
            $table->unsignedBigInteger('lot_id')->nullable()->index();
            $table->foreign('flyer_template_id', 'flyers_template_id_fk')->references('id')->on('flyer_templates')->nullOnDelete();
            $table->foreign('project_id', 'flyers_project_id_fk')->references('id')->on('projects')->nullOnDelete();
            $table->foreign('lot_id', 'flyers_lot_id_fk')->references('id')->on('lots')->nullOnDelete();
            $table->unsignedBigInteger('poster_img_id')->nullable();
            $table->unsignedBigInteger('floorplan_img_id')->nullable();
            $table->text('notes')->nullable();
            $table->boolean('is_custom')->default(false);
            $table->text('custom_html')->nullable();
            $table->text('custom_css')->nullable();
            $table->bigInteger('legacy_id')->nullable()->index();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('flyers');
    }
};
