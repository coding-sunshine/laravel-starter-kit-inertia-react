<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\Contact;
use App\Services\PrismService;
use Throwable;

/**
 * Generate AI-powered next best action suggestions for a contact using Prism.
 */
final readonly class GeneratePredictiveSuggestionsAction
{
    public function __construct(private PrismService $prism)
    {
        //
    }

    /**
     * @return array<int, array{action: string, reason: string, priority: string}>
     */
    public function handle(Contact $contact): array
    {
        if (! $this->prism->isAvailable()) {
            return [];
        }

        try {
            $context = $this->buildContext($contact);

            $response = $this->prism->text()
                ->withSystemPrompt(
                    'You are a CRM assistant. Return ONLY a JSON array of next best actions. '.
                    'Each item must have: action (string), reason (string), priority (high|medium|low). '.
                    'Return 3-5 suggestions. No markdown, no explanation, just the JSON array.'
                )
                ->withPrompt($context)
                ->generate();

            $decoded = json_decode($response->text, true);

            if (is_array($decoded)) {
                return array_slice($decoded, 0, 5);
            }

            return [];
        } catch (Throwable) {
            return [];
        }
    }

    private function buildContext(Contact $contact): string
    {
        $name = mb_trim($contact->first_name.' '.($contact->last_name ?? ''));
        $type = $contact->type ?? 'unknown';
        $stage = $contact->stage ?? 'unknown';
        $lastContacted = $contact->last_contacted_at?->diffForHumans() ?? 'never';
        $nextFollowup = $contact->next_followup_at?->diffForHumans() ?? 'not scheduled';
        $leadScore = $contact->lead_score ?? 0;

        return <<<CONTEXT
        Contact: {$name}
        Type: {$type}
        Stage: {$stage}
        Lead Score: {$leadScore}
        Last Contacted: {$lastContacted}
        Next Follow-up: {$nextFollowup}

        Based on this contact's profile, what are the 3-5 most important next actions?
        CONTEXT;
    }
}
