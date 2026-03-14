<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('property_searches', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('client_contact_id')->nullable()->constrained('contacts')->nullOnDelete();
            $table->foreignId('agent_contact_id')->nullable()->constrained('contacts')->nullOnDelete();
            $table->foreignId('logged_in_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->json('preferred_states')->nullable();
            $table->json('preferred_project_types')->nullable();
            $table->decimal('budget_min', 12, 2)->nullable();
            $table->decimal('budget_max', 12, 2)->nullable();
            $table->integer('bedrooms_min')->nullable();
            $table->integer('bedrooms_max')->nullable();
            $table->text('notes')->nullable();
            $table->unsignedBigInteger('legacy_id')->nullable()->index();
            $table->unsignedBigInteger('created_by')->nullable()->index();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('property_searches');
    }
};
