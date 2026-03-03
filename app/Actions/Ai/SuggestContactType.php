<?php

declare(strict_types=1);

namespace App\Actions\Ai;

use App\Models\Contact;
use App\Services\PrismService;
use Throwable;

final readonly class SuggestContactType
{
    public function __construct(private PrismService $prism) {}

    public function handle(Contact $contact): ?string
    {
        if (! $this->prism->isAvailable()) {
            return null;
        }

        $contact->loadMissing(['emails']);

        $name = mb_trim($contact->first_name.' '.$contact->last_name);
        $company = $contact->company ?? '—';
        $source = $contact->source ?? '—';
        $emails = $contact->emails->pluck('email')->implode(', ') ?: '—';

        $prompt = <<<PROMPT
            Based on the following contact information, suggest the most appropriate contact type.
            Return ONLY one of: lead, client, agent.

            Name: {$name}
            Company: {$company}
            Source: {$source}
            Emails: {$emails}
            PROMPT;

        try {
            $response = $this->prism->generate($prompt);

            return $response->text;
        } catch (Throwable) {
            return null;
        }
    }
}
