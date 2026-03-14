<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('property_enquiries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('client_contact_id')->nullable()->constrained('contacts')->nullOnDelete();
            $table->foreignId('agent_contact_id')->nullable()->constrained('contacts')->nullOnDelete();
            $table->foreignId('logged_in_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('lot_id')->nullable()->constrained('lots')->nullOnDelete();
            $table->foreignId('project_id')->nullable()->constrained('projects')->nullOnDelete();
            $table->string('status')->default('new')->index();
            $table->text('message')->nullable();
            $table->string('budget_min')->nullable();
            $table->string('budget_max')->nullable();
            $table->unsignedBigInteger('legacy_id')->nullable()->index();
            $table->unsignedBigInteger('created_by')->nullable()->index();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('property_enquiries');
    }
};
