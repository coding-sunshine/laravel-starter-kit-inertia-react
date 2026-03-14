<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\Lot;
use App\Services\PrismService;
use Throwable;

/**
 * Generate an AI-powered marketing property description using Prism.
 */
final readonly class GeneratePropertyDescriptionAction
{
    public function __construct(private PrismService $prism)
    {
        //
    }

    /**
     * @param  'professional'|'exciting'|'casual'  $tone
     */
    public function handle(Lot $lot, string $tone = 'professional'): string
    {
        $context = $this->buildContext($lot);

        if ($this->prism->isAvailable()) {
            try {
                $response = $this->prism->text()
                    ->withSystemPrompt("You are a real estate copywriter. Generate a {$tone} marketing description for a property.")
                    ->withPrompt($context)
                    ->generate();

                return $response->text;
            } catch (Throwable) {
                return $this->fallbackDescription($lot);
            }
        }

        return $this->fallbackDescription($lot);
    }

    private function buildContext(Lot $lot): string
    {
        $attributes = collect($lot->getAttributes())
            ->except(['id', 'created_at', 'updated_at', 'deleted_at'])
            ->filter()
            ->map(fn ($v, $k) => "{$k}: {$v}")
            ->implode("\n");

        return <<<PROMPT
        Generate a compelling property description for the following lot:

        {$attributes}

        Requirements:
        - 2-3 paragraphs
        - Highlight key features
        - Include a call-to-action at the end
        PROMPT;
    }

    private function fallbackDescription(Lot $lot): string
    {
        $name = $lot->getAttribute('name') ?? "Lot #{$lot->getKey()}";

        return "Discover {$name} — a premium property opportunity in a sought-after location. Contact us today to learn more about this exceptional listing.";
    }
}
