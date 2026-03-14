<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\Contact;
use App\Services\PrismService;
use Throwable;

/**
 * Generate a detailed lead brief / contact profile from available data using Prism AI.
 */
final readonly class GenerateLeadBriefAction
{
    public function __construct(private PrismService $prism)
    {
        //
    }

    /**
     * @return array<string, string>
     */
    public function handle(Contact $contact): array
    {
        $contactData = $this->extractContactData($contact);

        if (! $this->prism->isAvailable()) {
            return $this->fallbackBrief($contact, $contactData);
        }

        try {
            $prompt = $this->buildPrompt($contactData);
            $response = $this->prism->text()->withPrompt($prompt)->generate();

            return [
                'brief' => $response->text,
                'generated_at' => now()->toIso8601String(),
                'model' => 'ai-generated',
            ];
        } catch (Throwable) {
            return $this->fallbackBrief($contact, $contactData);
        }
    }

    /**
     * @return array<string, mixed>
     */
    private function extractContactData(Contact $contact): array
    {
        $contact->loadMissing(['emails', 'phones', 'propertySearches', 'tasks']);

        return [
            'name' => mb_trim("{$contact->first_name} {$contact->last_name}"),
            'type' => $contact->type,
            'stage' => $contact->stage,
            'origin' => $contact->contact_origin,
            'company' => $contact->company_name,
            'job_title' => $contact->job_title,
            'lead_score' => $contact->lead_score,
            'emails_count' => $contact->emails?->count() ?? 0,
            'phones_count' => $contact->phones?->count() ?? 0,
            'searches_count' => $contact->propertySearches?->count() ?? 0,
            'tasks_count' => $contact->tasks?->count() ?? 0,
            'last_contacted_at' => $contact->last_contacted_at?->diffForHumans(),
            'created_at' => $contact->created_at->format('d M Y'),
        ];
    }

    private function buildPrompt(array $data): string
    {
        $dataStr = collect($data)->map(fn ($v, $k) => "{$k}: {$v}")->implode("\n");

        return <<<PROMPT
        You are a real estate CRM assistant. Generate a detailed, professional lead brief for this contact.

        Contact Data:
        {$dataStr}

        Write a 2-3 paragraph profile that covers:
        1. Who they are and their buyer intent
        2. Their engagement level and priority
        3. Recommended next steps for the agent

        Be concise, professional, and action-oriented.
        PROMPT;
    }

    /**
     * @param  array<string, mixed>  $contactData
     * @return array<string, string>
     */
    private function fallbackBrief(Contact $contact, array $contactData): array
    {
        $name = $contactData['name'];
        $stage = ucfirst($contactData['stage'] ?? 'unknown');
        $score = $contactData['lead_score'] ?? 'N/A';

        $brief = "{$name} is a {$stage} stage contact with a lead score of {$score}. ";
        $brief .= "They came through {$contactData['origin']} and have been in the system since {$contactData['created_at']}. ";
        $brief .= "They have {$contactData['searches_count']} property searches on record. ";
        $brief .= 'Recommended action: follow up within 24 hours with relevant property listings.';

        return [
            'brief' => $brief,
            'generated_at' => now()->toIso8601String(),
            'model' => 'rule-based',
        ];
    }
}
