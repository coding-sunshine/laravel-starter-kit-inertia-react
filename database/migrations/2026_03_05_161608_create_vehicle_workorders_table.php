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
        Schema::create('vehicle_workorders', function (Blueprint $table) {

            $table->id();

            $table->foreignId('siding_id')
                ->constrained('sidings')
                ->cascadeOnDelete();

            $table->string('vehicle_no')->nullable();
            $table->string('rcd_pin_no')->nullable();
            $table->string('transport_name')->nullable();

            $table->string('wo_no')->nullable();
            $table->string('wo_no_2')->nullable();

            $table->date('work_order_date')->nullable();
            $table->date('issued_date')->nullable();

            $table->string('proprietor_name')->nullable();
            $table->string('represented_by')->nullable();

            $table->string('place')->nullable();
            $table->text('address')->nullable();

            $table->integer('tyres')->nullable();
            $table->integer('tare_weight')->nullable();

            $table->string('mobile_no_1')->nullable();
            $table->string('mobile_no_2')->nullable();

            $table->string('owner_type')->nullable();

            $table->date('regd_date')->nullable();
            $table->date('permit_validity_date')->nullable();
            $table->date('tax_validity_date')->nullable();
            $table->date('fitness_validity_date')->nullable();
            $table->date('insurance_validity_date')->nullable();

            $table->string('maker_model')->nullable();
            $table->string('make')->nullable();
            $table->string('model')->nullable();

            $table->text('remarks')->nullable();

            $table->string('recommended_by')->nullable();
            $table->string('referenced')->nullable();

            $table->string('local_or_non_local')->nullable();

            $table->string('pan_no')->nullable();
            $table->string('gst_no')->nullable();

            $table->timestamps();

            $table->index('vehicle_no');
            $table->index('wo_no');
            $table->index('siding_id');

        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('vehicle_workorders');
    }
};
