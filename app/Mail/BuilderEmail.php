<?php

declare(strict_types=1);

namespace App\Mail;

use App\Models\Project;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * Email sent to builders from the CRM.
 *
 * Supports four template types:
 * - price_list: Request price list and availability
 * - more_info: Request more information about a project
 * - hold_request: Request to hold a lot/property
 * - property_request: Request specific property details
 */
final class BuilderEmail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public readonly string $templateType,
        public readonly string $senderName,
        public readonly string $senderEmail,
        public readonly ?Project $project,
        public readonly string $message,
        public readonly array $payload = [],
    ) {
        //
    }

    public function envelope(): Envelope
    {
        $subject = match ($this->templateType) {
            'price_list' => 'Price List & Availability Request'.($this->project ? " — {$this->project->title}" : ''),
            'more_info' => 'Request for More Information'.($this->project ? " — {$this->project->title}" : ''),
            'hold_request' => 'Hold Request'.($this->project ? " — {$this->project->title}" : ''),
            'property_request' => 'Property Request'.($this->project ? " — {$this->project->title}" : ''),
            default => 'CRM Enquiry'.($this->project ? " — {$this->project->title}" : ''),
        };

        return new Envelope(subject: $subject);
    }

    public function content(): Content
    {
        return new Content(
            markdown: "emails.builder.{$this->templateType}",
            with: [
                'templateType' => $this->templateType,
                'senderName' => $this->senderName,
                'senderEmail' => $this->senderEmail,
                'project' => $this->project,
                'customMessage' => $this->message,
                'payload' => $this->payload,
            ],
        );
    }
}
