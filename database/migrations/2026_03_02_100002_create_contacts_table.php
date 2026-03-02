<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('contacts', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('organization_id')->nullable()->constrained()->nullOnDelete();
            $table->string('first_name');
            $table->string('last_name')->nullable();
            $table->string('job_title')->nullable();
            $table->string('type')->default('lead'); // lead, client, agent, partner, subscriber, bdm, affiliate
            $table->string('stage')->nullable();
            $table->foreignId('source_id')->nullable()->constrained('sources')->nullOnDelete();
            $table->foreignId('company_id')->nullable()->constrained('companies')->nullOnDelete();
            $table->string('company_name')->nullable(); // denormalized when company_id not set
            $table->json('extra_attributes')->nullable();
            $table->timestamp('last_followup_at')->nullable();
            $table->timestamp('next_followup_at')->nullable();
            $table->unsignedBigInteger('legacy_lead_id')->nullable()->unique(); // for import map (old leads.id)
            $table->timestamps();
            $table->softDeletes();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();

            $table->index('organization_id');
            $table->index('type');
            $table->index('stage');
            $table->index('legacy_lead_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('contacts');
    }
};
