<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Services\ModelRegistry;
use App\Services\SeedSpecGenerator;
use Exception;
use Illuminate\Console\Command;
use Prism\Prism\Enums\Provider;
use Prism\Prism\Facades\Prism;

final class SeedsReviewCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'seeds:review
                            {--model= : Review specific model only}
                            {--dry-run : Show review prompts without calling AI}
                            {--provider= : AI provider (openai, anthropic, local)}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'AI-based review of seeders and specs';

    /**
     * Execute the console command.
     */
    public function handle(SeedSpecGenerator $specGenerator, ModelRegistry $registry): int
    {
        $specificModel = $this->option('model');
        $dryRun = $this->option('dry-run');
        $provider = $this->option('provider') ?? 'local';

        $models = $specificModel
            ? ["App\\Models\\{$specificModel}"]
            : $registry->getAllModels();

        if (empty($models)) {
            $this->info('No models found.');

            return self::SUCCESS;
        }

        $this->info('Reviewing seeders and specs...');
        $this->newLine();

        if ($dryRun) {
            $this->warn('DRY RUN MODE - Review prompts will be shown but AI will not be called');
            $this->newLine();
        }

        $issues = [];

        foreach ($models as $modelClass) {
            $modelName = class_basename($modelClass);

            try {
                $spec = $specGenerator->loadSpec($modelClass);
                $seederInfo = $registry->hasSeeder($modelClass);

                if ($spec === null) {
                    $issues[] = [
                        'model' => $modelName,
                        'severity' => 'error',
                        'message' => 'Missing seed spec',
                    ];

                    continue;
                }

                if (! $seederInfo['exists']) {
                    $issues[] = [
                        'model' => $modelName,
                        'severity' => 'error',
                        'message' => 'Missing seeder',
                    ];

                    continue;
                }

                // Build review prompt
                $prompt = $this->buildReviewPrompt($modelName, $spec, $seederInfo);

                if ($dryRun) {
                    $this->line("  {$modelName} Review Prompt:");
                    $this->line('  '.str_repeat('-', 60));
                    $this->line($prompt);
                    $this->line('  '.str_repeat('-', 60));
                    $this->newLine();
                } else {
                    $review = $this->callAIReview($prompt, $provider);

                    if ($review !== null) {
                        $this->displayReview($modelName, $review);
                    }
                }
            } catch (Exception $e) {
                $this->error("  {$modelName}: Error - {$e->getMessage()}");
            }
        }

        if (! empty($issues)) {
            $this->newLine();
            $this->warn('Issues found:');

            foreach ($issues as $issue) {
                $this->line("  [{$issue['severity']}] {$issue['model']}: {$issue['message']}");
            }
        }

        return self::SUCCESS;
    }

    /**
     * Build review prompt.
     */
    private function buildReviewPrompt(string $modelName, array $spec, array $seederInfo): string
    {
        $prompt = "Review the seeding setup for model: {$modelName}\n\n";
        $prompt .= "Seed Spec:\n";
        $prompt .= json_encode($spec, JSON_PRETTY_PRINT)."\n\n";
        $prompt .= "Seeder Category: {$seederInfo['category']}\n\n";
        $prompt .= "Please review:\n";
        $prompt .= "1. Are all relationships properly handled in the seeder?\n";
        $prompt .= "2. Is the seeding logic idempotent (safe to run multiple times)?\n";
        $prompt .= "3. Are the example values in JSON realistic?\n";
        $prompt .= "4. Are there any potential issues or improvements?\n\n";
        $prompt .= 'Return a JSON object with: {issues: [], suggestions: []}';

        return $prompt;
    }

    /**
     * Call AI for review.
     *
     * @return array<string, mixed>|null
     */
    private function callAIReview(string $prompt, string $provider): ?array
    {
        try {
            // Map provider string to Prism Provider enum
            $prismProvider = match ($provider) {
                'openrouter', 'openrouter' => Provider::OpenRouter,
                'openai' => Provider::OpenAI,
                'anthropic' => Provider::Anthropic,
                default => Provider::OpenRouter, // Default to OpenRouter
            };

            // Use a model that supports structured output
            $model = match ($prismProvider) {
                Provider::OpenRouter => 'openai/gpt-4o-mini', // OpenRouter model
                Provider::OpenAI => 'gpt-4o-mini',
                Provider::Anthropic => 'claude-3-5-sonnet-20241022',
                default => 'openai/gpt-4o-mini',
            };

            $response = Prism::text()
                ->using($prismProvider, $model)
                ->withPrompt($prompt)
                ->asText();

            $text = $response->text;

            // Try to parse JSON from the response
            $jsonData = json_decode($text, true);

            if (json_last_error() !== JSON_ERROR_NONE) {
                // If not valid JSON, try to extract JSON from markdown code blocks
                if (preg_match('/```(?:json)?\s*(\{.*?\})\s*```/s', $text, $matches)) {
                    $jsonData = json_decode($matches[1], true);
                } elseif (preg_match('/\{.*\}/s', $text, $matches)) {
                    $jsonData = json_decode($matches[0], true);
                }
            }

            if ($jsonData === null || json_last_error() !== JSON_ERROR_NONE) {
                $this->error('Failed to parse JSON from AI response: '.json_last_error_msg());
                $this->line('Raw response: '.mb_substr($text, 0, 200));

                return null;
            }

            // Ensure required structure
            return [
                'issues' => $jsonData['issues'] ?? [],
                'suggestions' => $jsonData['suggestions'] ?? [],
            ];
        } catch (Exception $e) {
            $this->error('AI call failed: '.$e->getMessage());

            return null;
        }
    }

    /**
     * Display review results.
     *
     * @param  array<string, mixed>  $review
     */
    private function displayReview(string $modelName, array $review): void
    {
        $this->line("  {$modelName}:");

        $issues = $review['issues'] ?? [];

        if (! empty($issues)) {
            $this->warn('    Issues:');

            foreach ($issues as $issue) {
                $this->line("      - {$issue}");
            }
        }

        $suggestions = $review['suggestions'] ?? [];

        if (! empty($suggestions)) {
            $this->info('    Suggestions:');

            foreach ($suggestions as $suggestion) {
                $this->line("      - {$suggestion}");
            }
        }
    }
}
