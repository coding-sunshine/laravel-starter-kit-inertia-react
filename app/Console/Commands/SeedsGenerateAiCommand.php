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
        // This is a placeholder - actual AI integration would go here
        // For now, return null to indicate AI is not configured
        $this->warn('AI integration not configured. This is a placeholder implementation.');
        $this->info('To implement: Add your AI provider SDK and configure API keys.');

        return null;
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
