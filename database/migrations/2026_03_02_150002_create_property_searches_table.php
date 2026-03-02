<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('property_searches', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('organization_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('client_contact_id')->constrained('contacts')->cascadeOnDelete();
            $table->foreignId('agent_contact_id')->constrained('contacts')->cascadeOnDelete();
            $table->foreignId('logged_in_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->json('purchaser_type')->nullable();
            $table->json('property_type')->nullable();
            $table->string('no_of_bedrooms')->nullable();
            $table->string('no_of_bathrooms')->nullable();
            $table->string('no_of_carspaces')->nullable();
            $table->string('property_config_other')->nullable();
            $table->string('max_capacity')->nullable();
            $table->json('build_status')->nullable();
            $table->text('preferred_location')->nullable();
            $table->boolean('preapproval')->default(false);
            $table->string('lvr')->nullable();
            $table->string('lender')->nullable();
            $table->text('extra_instructions')->nullable();
            $table->string('finance')->nullable();
            $table->string('purchase_type')->nullable();
            $table->timestamps();
            $table->softDeletes();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();

            $table->index('organization_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('property_searches');
    }
};
