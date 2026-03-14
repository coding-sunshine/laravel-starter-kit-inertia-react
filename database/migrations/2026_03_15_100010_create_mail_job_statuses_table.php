<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('mail_job_statuses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('brochure_mail_job_id')->constrained('brochure_mail_jobs')->cascadeOnDelete();
            $table->string('status');
            $table->text('message')->nullable();
            $table->bigInteger('legacy_id')->nullable()->unique();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('mail_job_statuses');
    }
};
