<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('call_logs', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('contact_id')->nullable()->constrained('contacts')->nullOnDelete();
            $table->string('call_sid')->unique()->nullable();
            $table->string('direction')->default('inbound');
            $table->integer('duration_seconds')->default(0);
            $table->text('transcript')->nullable();
            $table->string('sentiment')->nullable();
            $table->string('outcome')->nullable();
            $table->json('vapi_metadata')->nullable();
            $table->timestamp('called_at');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('call_logs');
    }
};
