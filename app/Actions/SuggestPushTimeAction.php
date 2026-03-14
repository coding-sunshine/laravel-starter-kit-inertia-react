<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\Lot;
use App\Models\Project;
use Carbon\Carbon;
use EchoLabs\Prism\Enums\Provider;
use EchoLabs\Prism\Facades\Prism;
use Throwable;

final readonly class SuggestPushTimeAction
{
    /**
     * Suggest an optimal push time for a listing using AI.
     * Returns ['suggested_at' => Carbon, 'reason' => string].
     */
    public function handle(Lot|Project $listing, string $channel = 'php'): array
    {
        $type = $listing instanceof Lot ? 'lot' : 'project';
        $name = $listing instanceof Lot ? ($listing->title ?? "Lot {$listing->id}") : ($listing->name ?? "Project {$listing->id}");

        try {
            $response = Prism::text()
                ->using(Provider::OpenAI, 'gpt-4o-mini')
                ->withSystemPrompt('You are a real estate marketing specialist. Suggest optimal listing publication times.')
                ->withPrompt("For a {$type} listing '{$name}' on channel '{$channel}', suggest the best day and time to publish for maximum visibility. Reply in JSON: {\"day\":\"Tuesday\",\"time\":\"10:00\",\"reason\":\"...\"}. Keep reason under 20 words.")
                ->generate();

            $data = json_decode($response->text, true);

            if (is_array($data) && isset($data['day'], $data['time'])) {
                $suggested = Carbon::now()->next($data['day'])->setTimeFromTimeString($data['time']);

                return [
                    'suggested_at' => $suggested,
                    'reason' => $data['reason'] ?? 'Suggested based on engagement patterns.',
                ];
            }
        } catch (Throwable) {
            // Fall through to default
        }

        // Default: next Tuesday or Thursday at 10am
        $suggested = Carbon::now()->next('Tuesday')->setTimeFromTimeString('10:00');

        return [
            'suggested_at' => $suggested,
            'reason' => 'Mid-morning on weekdays typically drives highest property search traffic.',
        ];
    }
}
