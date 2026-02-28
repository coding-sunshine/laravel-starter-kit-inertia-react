<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tachograph_downloads', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignId('driver_id')->constrained()->cascadeOnDelete();

            $table->date('download_date');
            $table->string('file_path', 500)->nullable();
            $table->string('status', 50)->default('pending'); // pending, processed, failed, archived
            $table->timestamp('analysed_at')->nullable();

            $table->text('notes')->nullable();

            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['organization_id', 'driver_id', 'download_date']);
            $table->index(['driver_id', 'download_date']);
            $table->index(['status', 'download_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tachograph_downloads');
    }
};
