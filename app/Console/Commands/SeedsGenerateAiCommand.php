<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Services\AISeedGenerator;
use App\Services\ModelRegistry;
use App\Services\SeedSpecGenerator;
use Exception;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use Prism\Prism\Enums\Provider;
use Prism\Prism\Facades\Prism;

final class SeedsGenerateAiCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'seeds:generate-ai
                            {--model= : Generate for specific model only}
                            {--scenario=basic_demo : Scenario to generate (basic_demo, edge_cases, performance, i18n)}
                            {--provider= : AI provider (openai, anthropic, local)}
                            {--api-key= : API key for AI provider}
                            {--dry-run : Show prompts without calling AI}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Generate seed JSON data using AI (offline, curated)';

    /**
     * Execute the console command.
     */
    public function handle(
        SeedSpecGenerator $specGenerator,
        AISeedGenerator $aiGenerator,
        ModelRegistry $registry
    ): int {
        $specificModel = $this->option('model');
        $scenario = $this->option('scenario');
        $provider = $this->option('provider') ?? 'local';
        $apiKey = $this->option('api-key');
        $dryRun = $this->option('dry-run');

        $models = $specificModel
            ? ["App\\Models\\{$specificModel}"]
            : $registry->getAllModels();

        if (empty($models)) {
            $this->info('No models found.');

            return self::SUCCESS;
        }

        $this->info("Generating AI seed data (scenario: {$scenario})...");
        $this->newLine();

        if ($dryRun) {
            $this->warn('DRY RUN MODE - Prompts will be shown but AI will not be called');
            $this->newLine();
        }

        foreach ($models as $modelClass) {
            $modelName = class_basename($modelClass);

            try {
                $spec = $specGenerator->loadSpec($modelClass);

                if ($spec === null) {
                    $this->warn("  {$modelName}: No spec found (run seeds:spec-sync first)");

                    continue;
                }

                $profile = $aiGenerator->loadProfile($modelClass);

                if ($profile === null) {
                    // Generate profile from spec
                    $profile = $aiGenerator->generateProfile($modelClass, $spec);
                    $aiGenerator->saveProfile($modelClass, $profile);
                    $this->info("  {$modelName}: Created AI profile");
                }

                $prompt = $aiGenerator->buildPrompt($spec, $profile, $scenario);

                if ($dryRun) {
                    $this->line("  {$modelName} Prompt:");
                    $this->line('  '.str_repeat('-', 60));
                    $this->line($prompt);
                    $this->line('  '.str_repeat('-', 60));
                    $this->newLine();
                } else {
                    $jsonData = $this->callAI($prompt, $provider, $apiKey);

                    if ($jsonData !== null) {
                        $this->saveGeneratedJson($modelName, $jsonData, $scenario);
                        $this->info("  {$modelName}: Generated JSON data");
                    } else {
                        $this->error("  {$modelName}: Failed to generate data");
                    }
                }
            } catch (Exception $e) {
                $this->error("  {$modelName}: Error - {$e->getMessage()}");
            }
        }

        $this->newLine();

        if ($dryRun) {
            $this->info('Dry run complete. Run without --dry-run to generate data.');
        } else {
            $this->info('AI generation complete. Review generated JSON files before committing.');
        }

        return self::SUCCESS;
    }

    /**
     * Call AI provider to generate data.
     *
     * @return array<int, array<string, mixed>>|null
     */
    private function callAI(string $prompt, string $provider, ?string $apiKey): ?array
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

            // Ensure we return an array of records
            if (isset($jsonData['data']) && is_array($jsonData['data'])) {
                return $jsonData['data'];
            }

            if (isset($jsonData[0]) && is_array($jsonData[0])) {
                return $jsonData;
            }

            // If it's a single object, wrap it in an array
            if (is_array($jsonData) && ! isset($jsonData[0])) {
                return [$jsonData];
            }

            return null;
        } catch (Exception $e) {
            $this->error('AI call failed: '.$e->getMessage());

            return null;
        }
    }

    /**
     * Save generated JSON data.
     *
     * @param  array<int, array<string, mixed>>  $jsonData
     */
    private function saveGeneratedJson(string $modelName, array $jsonData, string $scenario): void
    {
        $jsonKey = Str::snake(Str::plural($modelName));
        $jsonPath = database_path("seeders/data/{$jsonKey}.json");

        $existingData = [];

        if (File::exists($jsonPath)) {
            $existingContent = File::get($jsonPath);
            $existingData = json_decode($existingContent, true) ?? [];
        }

        // Add scenario-based data
        if (! isset($existingData['_scenarios'])) {
            $existingData['_scenarios'] = [];
        }

        $existingData['_scenarios'][$scenario] = $jsonData;
        $existingData['_source'] = 'ai';
        $existingData['_generated_at'] = now()->toIso8601String();

        // Also update main data if scenario is basic_demo
        if ($scenario === 'basic_demo') {
            $existingData[$jsonKey] = $jsonData;
        }

        File::put($jsonPath, json_encode($existingData, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
    }
}
