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
        Schema::create('dispatch_reports', function (Blueprint $table) {
            $table->id();

            $table->foreignId('siding_id')
                ->constrained('sidings')
                ->cascadeOnDelete();

            $table->string('e_challan_no');
            $table->unsignedInteger('ref_no')->nullable();
            $table->date('issued_on')->nullable();
            $table->string('truck_no')->nullable();
            $table->string('shift')->nullable();
            $table->date('date')->nullable();
            $table->integer('trips')->nullable();
            $table->string('wo_no')->nullable(); // from mine
            $table->string('transport_name')->nullable();

            $table->decimal('mineral_wt', 10, 2)->nullable();
            $table->decimal('gross_wt_siding_rec_wt', 10, 2)->nullable();
            $table->decimal('tare_wt', 10, 2)->nullable();
            $table->decimal('net_wt_siding_rec_wt', 10, 2)->nullable();

            $table->integer('tyres')->nullable();
            $table->decimal('coal_ton_variation', 10, 2)->nullable();

            $table->dateTime('reached_datetime')->nullable();
            $table->string('time_taken_trip')->nullable();

            $table->text('remarks')->nullable();
            $table->string('wb')->nullable(); // from siding
            $table->string('trip_id_no')->nullable();

            $table->timestamps();

            $table->unique(['siding_id', 'e_challan_no']);
            $table->index('siding_id');
            $table->index('date');
            $table->index('truck_no');
            $table->index('e_challan_no');
            $table->index('trip_id_no');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('dispatch_reports');
    }
};
