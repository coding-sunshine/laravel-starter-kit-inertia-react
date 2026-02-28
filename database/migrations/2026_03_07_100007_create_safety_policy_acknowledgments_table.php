<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('safety_policy_acknowledgments', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('driver_id')->nullable()->constrained()->nullOnDelete();

            $table->string('policy_type', 100);
            $table->string('policy_reference', 100)->nullable();
            $table->string('policy_version', 50)->nullable();
            $table->timestamp('acknowledged_at');
            $table->string('ip_address', 45)->nullable();

            $table->timestamps();

            $table->index(['organization_id', 'policy_type']);
            $table->index(['user_id', 'acknowledged_at']);
            $table->index(['driver_id', 'acknowledged_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('safety_policy_acknowledgments');
    }
};
