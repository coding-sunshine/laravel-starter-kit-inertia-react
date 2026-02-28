<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('api_integrations', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();

            $table->string('integration_name', 200);
            $table->string('integration_type', 50);
            $table->string('provider_name', 200);

            $table->string('api_endpoint', 500)->nullable();
            $table->string('api_key_encrypted', 500)->nullable();
            $table->string('authentication_type', 50);
            $table->json('authentication_config')->nullable();

            $table->string('data_sync_frequency', 20)->default('daily');
            $table->timestamp('last_sync_timestamp')->nullable();
            $table->string('sync_status', 20)->default('active');

            $table->unsignedSmallInteger('error_count')->default(0);
            $table->text('last_error_message')->nullable();

            $table->unsignedInteger('rate_limit_per_hour')->nullable();
            $table->unsignedInteger('monthly_usage_count')->default(0);
            $table->unsignedInteger('monthly_limit')->nullable();

            $table->string('webhook_url', 500)->nullable();
            $table->string('webhook_secret_encrypted', 500)->nullable();

            $table->boolean('is_active')->default(true);

            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();
            $table->foreignId('deleted_by')->nullable()->constrained('users')->nullOnDelete();

            $table->index(['organization_id', 'integration_type', 'is_active']);
            $table->index(['sync_status', 'last_sync_timestamp']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('api_integrations');
    }
};
