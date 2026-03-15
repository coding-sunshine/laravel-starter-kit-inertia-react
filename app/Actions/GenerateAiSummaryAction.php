<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\AiSummary;
use App\Services\PrismService;
use Illuminate\Database\Eloquent\Model;
use Throwable;

/**
 * Generate an AI-powered summary for any Eloquent model using Prism.
 */
final readonly class GenerateAiSummaryAction
{
    public function __construct(private PrismService $prism)
    {
        //
    }

    public function handle(Model $model, string $context): AiSummary
    {
        $content = '';
        $usedModel = $this->prism->defaultModel();

        // Build richer context for contacts
        if ($model instanceof \App\Models\Contact) {
            $context = $this->buildContactContext($model, $context);
        }

        if ($this->prism->isAvailable()) {
            try {
                $response = $this->prism->text()
                    ->withSystemPrompt(
                        'You are a CRM assistant for a real estate investment platform. '
                        .'Generate a concise 2-3 sentence summary of this contact, focusing on: '
                        .'their engagement level, buying intent, key interests, and recommended next actions. '
                        .'Be specific and actionable.'
                    )
                    ->withPrompt($context)
                    ->generate();

                $content = $response->text;
            } catch (Throwable) {
                $content = $this->fallbackSummary($context);
            }
        } else {
            $content = $this->fallbackSummary($context);
        }

        return AiSummary::query()->create([
            'summarizable_type' => $model->getMorphClass(),
            'summarizable_id' => $model->getKey(),
            'content' => $content,
            'model' => $usedModel,
            'created_at' => now(),
        ]);
    }

    /**
     * Build rich context for a contact including activity data.
     */
    private function buildContactContext(\App\Models\Contact $contact, string $baseContext): string
    {
        $contact->loadMissing(['callLogs', 'tasks', 'propertySearches', 'strategyTags', 'emails', 'phones']);

        $parts = [$baseContext];

        // Recent activities
        $recentCalls = $contact->callLogs()->latest()->take(5)->get();
        if ($recentCalls->isNotEmpty()) {
            $callSummary = $recentCalls->map(fn ($call) => $call->created_at?->format('M j').': '.mb_substr((string) ($call->notes ?? $call->summary ?? 'Call'), 0, 80))->implode('; ');
            $parts[] = "Recent calls: {$callSummary}";
        }

        // Open tasks
        $openTasks = $contact->tasks()->where('status', '!=', 'completed')->latest()->take(3)->get();
        if ($openTasks->isNotEmpty()) {
            $taskSummary = $openTasks->pluck('title')->implode(', ');
            $parts[] = "Open tasks: {$taskSummary}";
        }

        // Property searches
        $searches = $contact->propertySearches()->latest()->take(3)->get();
        if ($searches->isNotEmpty()) {
            $searchSummary = $searches->map(fn ($s) => implode(' ', array_filter([
                $s->preferred_states ? implode('/', (array) $s->preferred_states) : null,
                $s->budget_min ? '$'.number_format($s->budget_min) : null,
                $s->budget_max ? '-$'.number_format($s->budget_max) : null,
            ])))->filter()->implode('; ');

            if ($searchSummary !== '') {
                $parts[] = "Property searches: {$searchSummary}";
            }
        }

        // Tags
        $tags = $contact->strategyTags->pluck('name')->filter()->implode(', ');
        if ($tags !== '') {
            $parts[] = "Tags: {$tags}";
        }

        return implode("\n", array_filter($parts));
    }

    private function fallbackSummary(string $context): string
    {
        return 'Summary unavailable. Context: '.mb_substr($context, 0, 200);
    }
}
