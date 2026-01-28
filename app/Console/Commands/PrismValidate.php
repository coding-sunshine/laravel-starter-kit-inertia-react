<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Services\PrismService;
use Exception;
use Illuminate\Console\Command;
use Prism\Prism\Enums\Provider;
use Prism\Relay\Facades\Relay;
use ValueError;

final class PrismValidate extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'prism:validate';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Validate Prism and Relay configuration';

    /**
     * Execute the console command.
     */
    public function handle(PrismService $prism): int
    {
        $this->info('Validating Prism and Relay configuration...');
        $this->newLine();

        $errors = [];
        $warnings = [];

        // Validate Prism configuration
        $this->line('Checking Prism configuration...');

        $defaultProvider = config('prism.defaults.provider');
        $defaultModel = config('prism.defaults.model');

        if (! $defaultProvider) {
            $warnings[] = 'No default provider configured. Using "openrouter" as fallback.';
        } else {
            try {
                Provider::from($defaultProvider);
                $this->info("  ✓ Default provider: {$defaultProvider}");
            } catch (ValueError $e) {
                $errors[] = "Invalid default provider: {$defaultProvider}";
            }
        }

        if (! $defaultModel) {
            $warnings[] = 'No default model configured. Using "openai/gpt-4o-mini" as fallback.';
        } else {
            $this->info("  ✓ Default model: {$defaultModel}");
        }

        // Validate OpenRouter configuration
        $this->newLine();
        $this->line('Checking OpenRouter configuration...');

        $openRouterKey = config('prism.providers.openrouter.api_key');
        $openRouterUrl = config('prism.providers.openrouter.url');

        if (empty($openRouterKey)) {
            $errors[] = 'OPENROUTER_API_KEY is not set in .env';
        } else {
            $this->info('  ✓ OpenRouter API key is configured');
        }

        if (empty($openRouterUrl)) {
            $warnings[] = 'OPENROUTER_URL is not set. Using default: https://openrouter.ai/api/v1';
        } else {
            $this->info("  ✓ OpenRouter URL: {$openRouterUrl}");
        }

        // Validate Relay configuration
        $this->newLine();
        $this->line('Checking Relay configuration...');

        $relayServers = config('relay.servers', []);

        if (empty($relayServers)) {
            $warnings[] = 'No MCP servers configured in config/relay.php';
        } else {
            $this->info('  ✓ MCP servers configured: '.count($relayServers));

            foreach ($relayServers as $serverName => $serverConfig) {
                try {
                    $tools = Relay::tools($serverName);
                    $this->info("    ✓ {$serverName}: ".count($tools).' tools available');
                } catch (Exception $e) {
                    $warnings[] = "Could not load tools for {$serverName}: {$e->getMessage()}";
                }
            }
        }

        // Display results
        $this->newLine();

        if (! empty($warnings)) {
            $this->warn('Warnings:');
            foreach ($warnings as $warning) {
                $this->line("  ⚠ {$warning}");
            }
            $this->newLine();
        }

        if (! empty($errors)) {
            $this->error('Errors found:');
            foreach ($errors as $error) {
                $this->line("  ✗ {$error}");
            }
            $this->newLine();
            $this->error('Configuration validation failed.');

            return self::FAILURE;
        }

        $this->info('✓ Configuration validation passed!');

        return self::SUCCESS;
    }
}
