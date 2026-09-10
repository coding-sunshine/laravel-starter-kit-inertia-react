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
        Schema::create('siding_vehicle_dispatches', function (Blueprint $table) {

            $table->id();

            // Relationship
            $table->foreignId('siding_id')
                ->constrained('sidings')
                ->cascadeOnDelete();

            $table->unsignedInteger('serial_no')->nullable();
            $table->unsignedInteger('ref_no')->nullable();

            $table->string('permit_no', 50);
            $table->string('pass_no', 100);
            $table->string('stack_do_no', 100)->nullable();

            $table->timestamp('issued_on')->nullable();

            $table->string('truck_regd_no', 20);

            $table->string('mineral', 50);
            $table->string('mineral_type', 50)->nullable();
            $table->decimal('mineral_weight', 10, 2);

            $table->text('source')->nullable();
            $table->text('destination')->nullable();
            $table->text('consignee')->nullable();

            $table->string('check_gate', 100)->nullable();
            $table->unsignedInteger('distance_km')->nullable();
            $table->string('shift', 20)->nullable();

            $table->foreignId('created_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->timestamps();

            // Indexes (Important for performance)
            $table->index('permit_no');
            $table->index('truck_regd_no');
            $table->index('issued_on');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('siding_vehicle_dispatches');
    }
};
