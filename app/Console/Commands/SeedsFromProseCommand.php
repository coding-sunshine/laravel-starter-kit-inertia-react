<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Services\SeedSpecGenerator;
use Exception;
use Illuminate\Console\Command;
use Illuminate\Support\Str;
use Prism\Prism\Enums\Provider;
use Prism\Prism\Facades\Prism;

final class SeedsFromProseCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'seeds:from-prose
                            {description : Natural language description of the model/domain}
                            {--model= : Model name to generate spec for}
                            {--dry-run : Show generated spec without saving}
                            {--provider= : AI provider (openai, anthropic, local)}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Generate seed spec from natural language description';

    /**
     * Execute the console command.
     */
    public function handle(SeedSpecGenerator $specGenerator): int
    {
        $description = $this->argument('description');
        $modelName = $this->option('model');
        $dryRun = $this->option('dry-run');
        $provider = $this->option('provider') ?? 'local';

        if ($modelName === null) {
            $this->error('Model name is required. Use --model=ModelName');

            return self::FAILURE;
        }

        $this->info('Generating seed spec from description...');
        $this->newLine();

        $prompt = $this->buildProsePrompt($description, $modelName);

        if ($dryRun) {
            $this->line('Prompt:');
            $this->line(str_repeat('-', 60));
            $this->line($prompt);
            $this->line(str_repeat('-', 60));
            $this->newLine();
            $this->info('Dry run complete. Run without --dry-run to generate spec.');
        } else {
            $spec = $this->callAIForSpec($prompt, $provider, $modelName);

            if ($spec !== null) {
                $modelClass = "App\\Models\\{$modelName}";

                if (class_exists($modelClass)) {
                    $specGenerator->saveSpec($modelClass, $spec);
                    $this->info("Seed spec generated for {$modelName}");
                } else {
                    $this->warn("Model {$modelName} does not exist. Spec would be generated when model is created.");
                }
            } else {
                $this->error('Failed to generate spec from description.');
            }
        }

        return self::SUCCESS;
    }

    /**
     * Build prompt for prose-to-spec conversion.
     */
    private function buildProsePrompt(string $description, string $modelName): string
    {
        $prompt = "Convert this natural language description into a seed spec JSON structure:\n\n";
        $prompt .= "Description: {$description}\n";
        $prompt .= "Model Name: {$modelName}\n\n";
        $prompt .= "Generate a JSON seed spec with:\n";
        $prompt .= "- fields: array of field definitions (name, type, nullable, default)\n";
        $prompt .= "- relationships: array of relationship definitions (type, model)\n";
        $prompt .= "- value_hints: array of example values\n";
        $prompt .= "- scenarios: array of scenario names\n\n";
        $prompt .= 'Return only valid JSON matching the seed spec format.';

        return $prompt;
    }

    /**
     * Call AI to generate spec from prose.
     *
     * @return array<string, mixed>|null
     */
    private function callAIForSpec(string $prompt, string $provider, string $modelName): ?array
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

                // Return a basic structure as fallback
                return [
                    'model' => $modelName,
                    'table' => Str::snake(Str::plural($modelName)),
                    'fields' => [],
                    'relationships' => [],
                    'value_hints' => [],
                    'scenarios' => ['basic_demo'],
                ];
            }

            // Ensure required fields are present
            $jsonData['model'] = $jsonData['model'] ?? $modelName;
            $jsonData['table'] = $jsonData['table'] ?? Str::snake(Str::plural($modelName));

            return $jsonData;
        } catch (Exception $e) {
            $this->error('AI call failed: '.$e->getMessage());

            // Return a basic structure as fallback
            return [
                'model' => $modelName,
                'table' => Str::snake(Str::plural($modelName)),
                'fields' => [],
                'relationships' => [],
                'value_hints' => [],
                'scenarios' => ['basic_demo'],
            ];
        }
    }
}
