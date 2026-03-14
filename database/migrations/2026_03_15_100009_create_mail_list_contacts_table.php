<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('mail_list_contacts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('mail_list_id')->constrained('mail_lists')->cascadeOnDelete();
            $table->foreignId('contact_id')->nullable()->constrained('contacts')->nullOnDelete();
            $table->string('external_email')->nullable();
            $table->timestamps();
            $table->unique(['mail_list_id', 'contact_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('mail_list_contacts');
    }
};
