<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('alerts', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained('users')->onDelete('set null');
            $table->foreignId('siding_id')->nullable()->constrained('sidings')->onDelete('cascade');
            $table->foreignId('rake_id')->nullable()->constrained('rakes')->onDelete('cascade');
            $table->string('type'); // demurrage_60, demurrage_30, demurrage_0, overload, rr_mismatch, stock_low
            $table->string('title');
            $table->text('body')->nullable();
            $table->string('severity', 20)->default('info'); // info, warning, critical
            $table->string('status', 20)->default('active'); // active, resolved
            $table->timestamp('resolved_at')->nullable();
            $table->foreignId('resolved_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamps();

            $table->index(['siding_id', 'status']);
            $table->index(['rake_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('alerts');
    }
};
