<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\Contact;
use App\Services\PrismService;
use Illuminate\Support\Facades\DB;
use Prism\Prism\Enums\Provider;
use Prism\Prism\Facades\Prism;
use Throwable;

/**
 * Generate and store a vector embedding for a contact using OpenAI's embedding API.
 */
final readonly class GenerateContactEmbeddingAction
{
    public function __construct(private PrismService $prism)
    {
        //
    }

    public function handle(Contact $contact, string $type = 'full'): bool
    {
        $text = $this->buildTextRepresentation($contact);

        if ($text === '') {
            return false;
        }

        try {
            $embedding = Prism::embeddings()
                ->using(Provider::OpenAI, 'text-embedding-3-small')
                ->fromInput($text)
                ->asEmbeddings();

            $vector = $embedding->embeddings[0] ?? null;

            if ($vector === null) {
                return false;
            }

            $vectorString = '['.implode(',', $vector).']';

            DB::table('contact_embeddings')->updateOrInsert(
                ['contact_id' => $contact->id, 'type' => $type],
                [
                    'content' => $text,
                    'embedding' => $vectorString,
                    'updated_at' => now(),
                    'created_at' => DB::raw('COALESCE(created_at, NOW())'),
                ]
            );

            return true;
        } catch (Throwable) {
            return false;
        }
    }

    private function buildTextRepresentation(Contact $contact): string
    {
        $contact->loadMissing(['emails', 'phones', 'strategyTags', 'callLogs', 'tasks']);

        $parts = [
            $contact->first_name.' '.($contact->last_name ?? ''),
            $contact->type ? "Type: {$contact->type}" : null,
            $contact->company_name ? "Company: {$contact->company_name}" : null,
            $contact->stage ? "Stage: {$contact->stage}" : null,
            $contact->job_title ? "Job: {$contact->job_title}" : null,
            $contact->contact_origin ? "Origin: {$contact->contact_origin}" : null,
        ];

        // Tags
        $tags = $contact->strategyTags->pluck('name')->filter()->implode(', ');
        if ($tags !== '') {
            $parts[] = "Tags: {$tags}";
        }

        // Recent activity summary
        $recentCalls = $contact->callLogs->take(5)->count();
        $openTasks = $contact->tasks->where('status', '!=', 'completed')->count();

        if ($recentCalls > 0) {
            $parts[] = "Recent calls: {$recentCalls}";
        }
        if ($openTasks > 0) {
            $parts[] = "Open tasks: {$openTasks}";
        }

        return implode(' | ', array_filter($parts));
    }
}
