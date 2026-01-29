<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Services\PrismService;
use Exception;
use Illuminate\Console\Command;
use Prism\Prism\Enums\Provider;

use function Laravel\Prompts\textarea;

final class PrismExample extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'prism:example
                            {--model= : Model to use (defaults to config)}
                            {--prompt= : Prompt to send (or will prompt interactively)}
                            {--structured : Use structured output (requires --schema)}
                            {--schema= : Schema for structured output (JSON string or class name)}
                            {--stream : Stream the response}
                            {--tools= : Comma-separated list of MCP server names}
                            {--provider= : Override default provider}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Example command demonstrating Prism with OpenRouter';

    /**
     * Execute the console command.
     */
    public function handle(PrismService $prism): int
    {
        $model = $this->option('model');
        $prompt = $this->option('prompt') ?? textarea('Enter your prompt:', required: true);
        $useStructured = $this->option('structured');
        $schema = $this->option('schema');
        $stream = $this->option('stream');
        $tools = $this->option('tools');
        $provider = $this->option('provider');

        if ($useStructured && ! $schema) {
            $this->error('--schema is required when using --structured');

            return self::FAILURE;
        }

        $this->info('Sending request...');
        $this->newLine();

        try {
            if ($useStructured) {
                $parsedSchema = $this->parseSchema($schema);
                $response = $prism->generateStructured($prompt, $parsedSchema, $model);

                $this->info('Structured Response:');
                $this->line(json_encode($response, JSON_PRETTY_PRINT));

                return self::SUCCESS;
            }

            // Build request based on options
            if ($provider) {
                $request = $prism->using(Provider::from($provider), $model ?? $prism->defaultModel());
            } elseif ($tools) {
                $serverList = array_map(trim(...), explode(',', $tools));
                $request = $prism->withTools($serverList, $model);
            } else {
                $request = $prism->text($model);
            }

            $request = $request->withPrompt($prompt);

            if ($stream) {
                $this->info('Streaming response:');
                $this->newLine();

                $request->asStream();

                $this->newLine(2);

                return self::SUCCESS;
            }

            $response = $request->asText();

            $this->info('Response:');
            $this->line($response->text);

            return self::SUCCESS;
        } catch (Exception $e) {
            $this->error('Error: '.$e->getMessage());

            if ($this->option('verbose')) {
                $this->line('Stack trace:');
                $this->line($e->getTraceAsString());
            }

            return self::FAILURE;
        }
    }

    /**
     * Parse schema from string or return object.
     */
    private function parseSchema(string $schema): string|object
    {
        // Try to decode as JSON first
        $decoded = json_decode($schema, true);

        if (json_last_error() === JSON_ERROR_NONE) {
            return $decoded;
        }

        // Check if it's a class name
        if (class_exists($schema)) {
            return new $schema;
        }

        // Return as-is (might be a JSON string that needs parsing)
        return $schema;
    }
}
