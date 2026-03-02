<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('property_enquiries', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('organization_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('client_contact_id')->constrained('contacts')->cascadeOnDelete();
            $table->foreignId('agent_contact_id')->constrained('contacts')->cascadeOnDelete();
            $table->foreignId('logged_in_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->json('purchaser_type')->nullable();
            $table->string('max_capacity')->nullable();
            $table->text('preferred_location')->nullable();
            $table->boolean('preapproval')->default(false);
            $table->json('property')->nullable();
            $table->json('requesting_info')->nullable();
            $table->text('instructions')->nullable();
            $table->string('inspection_person')->nullable();
            $table->date('inspection_date')->nullable();
            $table->string('inspection_time')->nullable();
            $table->boolean('cash_purchase')->nullable();
            $table->timestamps();
            $table->softDeletes();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();

            $table->index('organization_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('property_enquiries');
    }
};
