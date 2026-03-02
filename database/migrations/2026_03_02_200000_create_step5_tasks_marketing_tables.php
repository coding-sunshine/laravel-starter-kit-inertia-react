<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // tasks
        Schema::create('tasks', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('organization_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('assigned_contact_id')->nullable()->constrained('contacts')->nullOnDelete();
            $table->foreignId('attached_contact_id')->nullable()->constrained('contacts')->nullOnDelete();
            $table->foreignId('assigned_to_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('title');
            $table->text('description')->nullable();
            $table->dateTime('due_at')->nullable();
            $table->string('priority')->nullable();
            $table->string('status')->nullable();
            $table->dateTime('completed_at')->nullable();
            $table->timestampsTz();
            $table->softDeletesTz();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
        });

        // relationships
        Schema::create('relationships', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('organization_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('account_contact_id')->constrained('contacts')->cascadeOnDelete();
            $table->foreignId('relation_contact_id')->constrained('contacts')->cascadeOnDelete();
            $table->string('type')->nullable();
            $table->timestampsTz();
        });

        // partners
        Schema::create('partners', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('organization_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('contact_id')->constrained('contacts')->cascadeOnDelete();
            $table->string('role')->nullable();
            $table->string('status')->nullable();
            $table->json('extra_attributes')->nullable();
            $table->timestampsTz();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
        });

        // mail_lists
        Schema::create('mail_lists', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('organization_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('owner_contact_id')->nullable()->constrained('contacts')->nullOnDelete();
            $table->string('name');
            $table->json('client_ids')->nullable(); // array of contact_ids
            $table->timestampsTz();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
        });

        // mail_job_statuses
        Schema::create('mail_job_statuses', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->string('code')->unique();
            $table->timestampsTz();
        });

        // brochure_mail_jobs
        Schema::create('brochure_mail_jobs', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('organization_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('owner_contact_id')->nullable()->constrained('contacts')->nullOnDelete();
            $table->foreignId('mail_list_id')->nullable()->constrained('mail_lists')->nullOnDelete();
            $table->foreignId('status_id')->nullable()->constrained('mail_job_statuses')->nullOnDelete();
            $table->dateTime('scheduled_at')->nullable();
            $table->json('client_contact_ids')->nullable();
            $table->string('name')->nullable();
            $table->timestampsTz();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
        });

        // website_contacts
        Schema::create('website_contacts', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('organization_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('contact_id')->nullable()->constrained('contacts')->nullOnDelete();
            $table->string('source')->nullable();
            $table->json('payload')->nullable();
            $table->timestampsTz();
        });

        // questionnaires
        Schema::create('questionnaires', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('organization_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('contact_id')->nullable()->constrained('contacts')->nullOnDelete();
            $table->string('title');
            $table->json('answers')->nullable();
            $table->timestampsTz();
        });

        // finance_assessments
        Schema::create('finance_assessments', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('organization_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('agent_contact_id')->nullable()->constrained('contacts')->nullOnDelete();
            $table->foreignId('logged_in_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->json('data')->nullable();
            $table->timestampsTz();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
        });

        // notes
        Schema::create('notes', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('organization_id')->nullable()->constrained()->nullOnDelete();
            $table->morphs('noteable');
            $table->foreignId('author_id')->nullable()->constrained('users')->nullOnDelete();
            $table->text('content');
            $table->string('type')->nullable();
            $table->timestampsTz();
        });

        // comments
        Schema::create('comments', function (Blueprint $table): void {
            $table->id();
            $table->morphs('commentable');
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->text('comment');
            $table->boolean('is_approved')->default(true);
            $table->timestampsTz();
        });

        // tags / taggables are provided by an earlier migration (2026_02_08_061704_create_tag_tables)

        // addresses
        Schema::create('addresses', function (Blueprint $table): void {
            $table->id();
            $table->morphs('addressable');
            $table->string('type')->nullable();
            $table->string('line1');
            $table->string('line2')->nullable();
            $table->string('city')->nullable();
            $table->string('state')->nullable();
            $table->string('postcode')->nullable();
            $table->string('country')->nullable();
            $table->timestampsTz();
        });

        // statuses
        Schema::create('statuses', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->string('code')->unique();
            $table->string('type')->nullable();
            $table->timestampsTz();
        });

        // statusables
        Schema::create('statusables', function (Blueprint $table): void {
            $table->foreignId('status_id')->constrained('statuses')->cascadeOnDelete();
            $table->morphs('statusable');
            $table->primary(['status_id', 'statusable_type', 'statusable_id']);
        });

        // onlineform_contacts
        Schema::create('onlineform_contacts', function (Blueprint $table): void {
            $table->id();
            $table->morphs('model'); // typically Contact
            $table->uuid('uuid')->unique();
            $table->timestampsTz();
        });

        // campaign_websites
        Schema::create('campaign_websites', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('organization_id')->nullable()->constrained()->nullOnDelete();
            $table->string('name');
            $table->string('domain')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestampsTz();
        });

        // campaign_website_templates
        Schema::create('campaign_website_templates', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->json('schema')->nullable();
            $table->timestampsTz();
        });

        // campaign_website_project (pivot)
        Schema::create('campaign_website_project', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('campaign_website_id')->constrained('campaign_websites')->cascadeOnDelete();
            $table->foreignId('project_id')->constrained('projects')->cascadeOnDelete();
            $table->timestampsTz();
        });

        // resources
        Schema::create('resources', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->text('description')->nullable();
            $table->string('url')->nullable();
            $table->timestampsTz();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('modified_by')->nullable()->constrained('users')->nullOnDelete();
        });

        // resource_groups
        Schema::create('resource_groups', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->timestampsTz();
        });

        // resource_categories
        Schema::create('resource_categories', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->timestampsTz();
        });

        // ad_managements
        Schema::create('ad_managements', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('organization_id')->nullable()->constrained()->nullOnDelete();
            $table->string('name');
            $table->json('config')->nullable();
            $table->timestampsTz();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
        });

        // column_management
        Schema::create('column_management', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('table');
            $table->json('visible_columns')->nullable();
            $table->json('hidden_columns')->nullable();
            $table->timestampsTz();
        });

        // widget_settings
        Schema::create('widget_settings', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('widget');
            $table->json('settings')->nullable();
            $table->timestampsTz();
        });

        // survey_questions
        Schema::create('survey_questions', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('questionnaire_id')->nullable()->constrained('questionnaires')->nullOnDelete();
            $table->string('question');
            $table->string('type')->default('text');
            $table->json('options')->nullable();
            $table->timestampsTz();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('survey_questions');
        Schema::dropIfExists('widget_settings');
        Schema::dropIfExists('column_management');
        Schema::dropIfExists('ad_managements');
        Schema::dropIfExists('resource_categories');
        Schema::dropIfExists('resource_groups');
        Schema::dropIfExists('resources');
        Schema::dropIfExists('campaign_website_project');
        Schema::dropIfExists('campaign_website_templates');
        Schema::dropIfExists('campaign_websites');
        Schema::dropIfExists('onlineform_contacts');
        Schema::dropIfExists('statusables');
        Schema::dropIfExists('statuses');
        Schema::dropIfExists('addresses');
        Schema::dropIfExists('taggables');
        Schema::dropIfExists('tags');
        Schema::dropIfExists('comments');
        Schema::dropIfExists('notes');
        Schema::dropIfExists('finance_assessments');
        Schema::dropIfExists('questionnaires');
        Schema::dropIfExists('website_contacts');
        Schema::dropIfExists('brochure_mail_jobs');
        Schema::dropIfExists('mail_job_statuses');
        Schema::dropIfExists('mail_lists');
        Schema::dropIfExists('partners');
        Schema::dropIfExists('relationships');
        Schema::dropIfExists('tasks');
    }
};

