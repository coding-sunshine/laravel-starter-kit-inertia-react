<?php

declare(strict_types=1);

namespace App\Actions\Ai;

use App\Models\Contact;
use App\Services\PrismService;
use Throwable;

final readonly class SuggestTasks
{
    public function __construct(private PrismService $prism) {}

    /**
     * @return array<int, string>
     */
    public function handle(Contact $contact): array
    {
        if (! $this->prism->isAvailable()) {
            return [];
        }

        $contact->loadMissing(['notes', 'tasks']);

        $name = mb_trim($contact->first_name.' '.$contact->last_name);
        $stage = $contact->stage ?? '—';
        $recentNotes = $contact->notes->sortByDesc('created_at')->take(5)->pluck('body')->filter()->implode("\n");
        $existingTasks = $contact->tasks->pluck('title')->implode(', ') ?: 'none';

        $prompt = <<<PROMPT
            Based on this contact's information, suggest 3-5 actionable task titles for a CRM user.
            Return each task on its own line, no numbering or bullets.

            Contact: {$name}
            Stage: {$stage}
            Existing tasks: {$existingTasks}
            Recent notes:
            {$recentNotes}
            PROMPT;

        try {
            $response = $this->prism->generate($prompt);

            return array_values(array_filter(
                array_map('trim', explode("\n", $response->text)),
                fn (string $line): bool => $line !== '',
            ));
        } catch (Throwable) {
            return [];
        }
    }
}
