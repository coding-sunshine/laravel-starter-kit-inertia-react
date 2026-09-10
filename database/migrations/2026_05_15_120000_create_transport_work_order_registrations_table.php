<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('transport_work_order_registrations', function (Blueprint $table): void {
            $table->id();

            $table->foreignId('siding_id')
                ->nullable()
                ->constrained('sidings')
                ->nullOnDelete();

            $table->string('work_order_no_1')->nullable();
            $table->string('work_order_no_2')->nullable()->unique();
            $table->string('reference_no')->nullable();
            $table->date('work_order_date')->nullable();
            $table->string('transporter_name')->nullable();
            $table->string('trade_name')->nullable();
            $table->text('legal_name_of_business')->nullable();
            $table->string('pan_card')->nullable();
            $table->string('gst_no')->nullable();
            $table->string('status')->nullable();
            $table->string('email')->nullable();
            $table->string('vendor_code')->nullable();
            $table->string('mobile_1')->nullable();
            $table->string('mobile_2')->nullable();
            $table->text('address')->nullable();
            $table->text('gramin_or_non_gramin')->nullable();

            $table->timestamps();

            $table->index('siding_id');
            $table->index('transporter_name');
            $table->index('work_order_no_1');
            $table->index('email');
            $table->index('vendor_code');
            $table->index('mobile_1');
            $table->index('mobile_2');
            $table->index('status');
            $table->index('gst_no');
            $table->index('pan_card');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('transport_work_order_registrations');
    }
};
