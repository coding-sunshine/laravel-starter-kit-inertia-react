<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\Contact;
use App\Services\PrismService;
use Prism\Prism\Schema\EnumSchema;
use Prism\Prism\Schema\ObjectSchema;
use Prism\Prism\Schema\StringSchema;
use Throwable;

/**
 * Classify a contact's type using AI structured output.
 */
final readonly class ClassifyContactTypeAction
{
    private const array VALID_TYPES = [
        'lead',
        'buyer',
        'seller',
        'investor',
        'sales_agent',
        'partner',
        'subscriber',
        'developer_rep',
        'referral_partner',
    ];

    public function __construct(private PrismService $prism)
    {
        //
    }

    /**
     * Classify and optionally update the contact's type.
     *
     * @return array{type: string, confidence: string}|null
     */
    public function handle(Contact $contact, bool $persist = false): ?array
    {
        if (! $this->prism->isAvailable()) {
            return null;
        }

        $contact->loadMissing(['callLogs', 'tasks', 'propertySearches', 'strategyTags']);

        $context = $this->buildClassificationContext($contact);

        try {
            $response = $this->prism->structured()
                ->withSystemPrompt(
                    'You are a CRM analyst for a real estate investment platform. '
                    .'Classify the contact into one of these types based on their profile and activity: '
                    .implode(', ', self::VALID_TYPES).'. '
                    .'Also indicate your confidence level.'
                )
                ->withPrompt($context)
                ->withSchema(new ObjectSchema(
                    name: 'contact_classification',
                    description: 'Contact type classification result',
                    properties: [
                        new EnumSchema(
                            name: 'type',
                            description: 'The classified contact type',
                            options: self::VALID_TYPES,
                        ),
                        new EnumSchema(
                            name: 'confidence',
                            description: 'Classification confidence',
                            options: ['high', 'medium', 'low'],
                        ),
                        new StringSchema(
                            name: 'reasoning',
                            description: 'Brief reasoning for the classification',
                        ),
                    ],
                    requiredFields: ['type', 'confidence', 'reasoning'],
                ))
                ->asStructured();

            /** @var array{type: string, confidence: string, reasoning: string} $result */
            $result = $response->structured;

            if ($persist && in_array($result['type'], self::VALID_TYPES, true)) {
                $contact->type = $result['type'];
                $contact->saveQuietly();
            }

            return [
                'type' => $result['type'],
                'confidence' => $result['confidence'],
            ];
        } catch (Throwable) {
            return null;
        }
    }

    private function buildClassificationContext(Contact $contact): string
    {
        $parts = [
            "Name: {$contact->first_name} {$contact->last_name}",
            "Current type: {$contact->type}",
            $contact->company_name ? "Company: {$contact->company_name}" : null,
            $contact->job_title ? "Job title: {$contact->job_title}" : null,
            $contact->stage ? "Stage: {$contact->stage}" : null,
            $contact->contact_origin ? "Origin: {$contact->contact_origin}" : null,
        ];

        $callCount = $contact->callLogs->count();
        if ($callCount > 0) {
            $parts[] = "Call interactions: {$callCount}";
        }

        $taskCount = $contact->tasks->count();
        if ($taskCount > 0) {
            $parts[] = "Tasks assigned: {$taskCount}";
        }

        $searchCount = $contact->propertySearches->count();
        if ($searchCount > 0) {
            $parts[] = "Property searches: {$searchCount}";
        }

        $tags = $contact->strategyTags->pluck('name')->filter()->implode(', ');
        if ($tags !== '') {
            $parts[] = "Tags: {$tags}";
        }

        return implode("\n", array_filter($parts));
    }
}
