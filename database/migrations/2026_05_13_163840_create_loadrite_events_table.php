<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('loadrite_events', function (Blueprint $table): void {
            $table->id();
            $table->string('event_id')->unique();
            $table->foreignId('siding_id')->nullable()->constrained('sidings')->nullOnDelete();
            $table->foreignId('rake_id')->nullable()->constrained('rakes')->nullOnDelete();
            $table->foreignId('wagon_id')->nullable()->constrained('wagons')->nullOnDelete();
            $table->unsignedInteger('wagon_sequence');
            $table->string('event_type', 16);
            $table->decimal('weight_mt', 12, 3);
            $table->timestamp('event_time')->nullable();
            $table->string('operator')->nullable();
            $table->string('scale_id', 64)->nullable();
            $table->string('product', 64)->nullable();
            $table->decimal('gps_lat', 10, 6)->nullable();
            $table->decimal('gps_lng', 10, 6)->nullable();
            $table->string('user_data1', 128)->nullable();
            $table->string('user_data2', 128)->nullable();
            $table->string('user_data3', 128)->nullable();
            $table->json('raw_payload')->nullable();
            $table->timestamps();

            $table->index(['rake_id', 'wagon_sequence']);
            $table->index(['wagon_id', 'event_time']);
            $table->index(['siding_id', 'event_time']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('loadrite_events');
    }
};
