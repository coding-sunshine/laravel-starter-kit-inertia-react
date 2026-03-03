<?php

declare(strict_types=1);

namespace App\Actions\Ai;

use App\Models\Contact;
use App\Services\PrismService;
use Throwable;

final readonly class SummarizeNotes
{
    public function __construct(private PrismService $prism) {}

    public function handle(Contact $contact): ?string
    {
        if (! $this->prism->isAvailable()) {
            return null;
        }

        $contact->loadMissing(['notes']);

        $notes = $contact->notes->pluck('body')->filter()->implode("\n---\n");

        if ($notes === '') {
            return null;
        }

        $prompt = <<<PROMPT
            Summarize the following contact notes in 2-3 concise sentences for internal CRM use.
            Focus on key interactions, decisions, and next steps.

            Notes:
            {$notes}
            PROMPT;

        try {
            $response = $this->prism->generate($prompt);

            return $response->text;
        } catch (Throwable) {
            return null;
        }
    }
}
