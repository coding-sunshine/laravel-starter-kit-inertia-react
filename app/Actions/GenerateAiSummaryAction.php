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

        if ($this->prism->isAvailable()) {
            try {
                $response = $this->prism->text()
                    ->withSystemPrompt('You are a CRM assistant. Generate a concise 2-3 sentence summary.')
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
            'model' => 'gpt-4o-mini',
            'created_at' => now(),
        ]);
    }

    private function fallbackSummary(string $context): string
    {
        return 'Summary unavailable. Context: '.mb_substr($context, 0, 200);
    }
}
