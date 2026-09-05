<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('daily_vehicle_entries', function (Blueprint $table) {
            $table->id();

            $table->foreignId('siding_id')->constrained()->cascadeOnDelete();

            $table->date('entry_date'); // Sheet date

            $table->integer('shift'); // 1st, 2nd, 3rd shift

            $table->string('e_challan_no')->nullable();
            $table->string('vehicle_no');
            $table->decimal('gross_wt', 10, 2)->nullable();
            $table->decimal('tare_wt', 10, 2)->nullable();
            $table->dateTime('reached_at')->default(DB::raw('CURRENT_TIMESTAMP'));
            $table->string('wb_no')->nullable();
            $table->string('d_challan_no')->nullable();

            $table->enum('challan_mode', ['offline', 'online'])->nullable();
            $table->string('status')->default('draft');
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();

            $table->timestamps();

            $table->index(['entry_date', 'shift']);
            $table->index(['siding_id', 'entry_date','vehicle_no','e_challan_no','challan_mode','status']);
            $table->unique([
                'siding_id',
                'entry_date',
                'shift',
                'vehicle_no',
                'reached_at'
            ]);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('daily_vehicle_entries');
    }
};
