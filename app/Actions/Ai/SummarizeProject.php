<?php

declare(strict_types=1);

namespace App\Actions\Ai;

use App\Models\Project;
use App\Services\PrismService;
use Throwable;

final readonly class SummarizeProject
{
    public function __construct(private PrismService $prism) {}

    public function handle(Project $project): ?string
    {
        if (! $this->prism->isAvailable()) {
            return null;
        }

        $title = $project->title ?? '—';
        $stage = $project->stage ?? '—';
        $priceFrom = $project->price_from !== null ? number_format($project->price_from, 2) : '—';
        $priceTo = $project->price_to !== null ? number_format($project->price_to, 2) : '—';
        $lotCount = $project->lots_count ?? $project->lots()->count();
        $features = $project->features ?? '—';

        $prompt = <<<PROMPT
            Summarize this property project in 2-3 sentences for internal use.

            Title: {$title}
            Stage: {$stage}
            Price range: {$priceFrom} – {$priceTo}
            Lot count: {$lotCount}
            Features: {$features}
            PROMPT;

        try {
            $response = $this->prism->generate($prompt);

            return $response->text;
        } catch (Throwable) {
            return null;
        }
    }
}
