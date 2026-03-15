<?php

declare(strict_types=1);

namespace App\Actions;

use App\Mail\BuilderEmail;
use App\Models\BuilderEmailLog;
use App\Models\Contact;
use App\Models\Project;
use App\Models\User;
use Illuminate\Support\Facades\Mail;

/**
 * Send an email to a builder from the CRM.
 *
 * Supports four template types: price_list, more_info, hold_request, property_request.
 * All sent emails are logged in builder_email_logs for activity tracking.
 */
final readonly class SendBuilderEmailAction
{
    /**
     * @param  array<string, mixed>  $payload
     */
    public function handle(
        User $sender,
        string $recipientEmail,
        string $recipientName,
        string $templateType,
        ?Project $project = null,
        ?Contact $contact = null,
        string $message = '',
        array $payload = [],
    ): BuilderEmailLog {
        $mailable = new BuilderEmail(
            templateType: $templateType,
            senderName: $sender->name,
            senderEmail: $sender->email,
            project: $project,
            message: $message,
            payload: $payload,
        );

        Mail::to($recipientEmail, $recipientName)->send($mailable);

        return BuilderEmailLog::query()->create([
            'organization_id' => tenant('id'),
            'contact_id' => $contact?->id,
            'project_id' => $project?->id,
            'sent_by' => $sender->id,
            'template_type' => $templateType,
            'recipient_email' => $recipientEmail,
            'recipient_name' => $recipientName,
            'subject' => $mailable->envelope()->subject,
            'payload' => array_merge($payload, ['message' => $message]),
            'status' => 'sent',
        ]);
    }
}
