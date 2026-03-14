<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('brochure_mail_jobs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignId('owner_contact_id')->nullable()->constrained('contacts')->nullOnDelete();
            $table->foreignId('mail_list_id')->nullable()->constrained('mail_lists')->nullOnDelete();
            $table->jsonb('client_contact_ids')->nullable();
            $table->string('status')->default('pending');
            $table->timestamp('scheduled_at')->nullable();
            $table->bigInteger('legacy_id')->nullable()->unique();
            $table->userstamps();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('brochure_mail_jobs');
    }
};
