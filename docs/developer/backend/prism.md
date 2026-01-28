# Prism AI Integration

This starter kit includes [Prism PHP](https://prismphp.com/) configured with OpenRouter for AI-powered features, along with [Prism Relay](https://github.com/prism-php/relay) for MCP (Model Context Protocol) server integration.

## Overview

The Prism integration provides:

- **Multiple Provider Support**: OpenRouter, OpenAI, Anthropic, and more
- **Helper Function**: Simple `ai()` helper for quick access
- **Service Wrapper**: `PrismService` for dependency injection
- **MCP Integration**: Relay support for external tools and APIs
- **Structured Output**: Transform AI responses into strongly-typed data
- **Tool Calling**: Empower AI with custom tools via MCP servers
- **Streaming**: Real-time response streaming
- **Multi-Modal**: Support for text, images, and audio
- **Document Support**: Send PDFs and documents to compatible models

## Configuration

Prism is configured in `config/prism.php` and uses environment variables from your `.env` file:

```env
OPENROUTER_API_KEY=your-api-key-here
OPENROUTER_URL=https://openrouter.ai/api/v1
OPENROUTER_SITE_HTTP_REFERER="${APP_URL}"
OPENROUTER_SITE_X_TITLE="${APP_NAME}"

PRISM_DEFAULT_PROVIDER=openrouter
PRISM_DEFAULT_MODEL=openai/gpt-4o-mini
```

## Usage

### Using the Helper Function

The simplest way to use Prism is via the `ai()` helper function:

```php
// Simple text generation
$response = ai()->generate('Explain Laravel in one sentence');

echo $response->text;
```

### Using the PrismService

You can also use the `PrismService` class directly:

```php
use App\Services\PrismService;

$prism = new PrismService;

// Simple text generation
$response = $prism->generate('Explain Laravel in one sentence');

echo $response->text;
```

### Direct Prism Usage

You can also use Prism directly for more advanced features:

```php
use Prism\Prism\Enums\Provider;
use Prism\Prism\Prism;

$response = Prism::text()
    ->using(Provider::OpenRouter, 'openai/gpt-4o-mini')
    ->withPrompt('Hello, how are you?')
    ->asText();

echo $response->text;
```

### Using Different Providers

Prism supports multiple providers. You can use any provider configured in `config/prism.php`:

```php
use App\Services\PrismService;
use Prism\Prism\Enums\Provider;

$prism = new PrismService;

// Use OpenAI directly
$response = $prism->using(Provider::OpenAI, 'gpt-4o-mini')
    ->withPrompt('Your prompt here')
    ->asText();

// Use Anthropic
$response = $prism->using(Provider::Anthropic, 'claude-3-5-sonnet-20241022')
    ->withPrompt('Your prompt here')
    ->asText();
```

### Available Models

OpenRouter supports many models from different providers. Use the OpenRouter model format:

- `openai/gpt-4o-mini`
- `openai/gpt-4o`
- `anthropic/claude-3-5-sonnet`
- `google/gemini-pro`
- And many more at [openrouter.ai/models](https://openrouter.ai/models)

### Using MCP Tools

You can easily use MCP tools with the service:

```php
use App\Services\PrismService;

$prism = new PrismService;

// Use tools from a single MCP server
$response = $prism->withTools('puppeteer')
    ->withPrompt('Navigate to laravel.com and summarize the homepage')
    ->asText();

// Use tools from multiple MCP servers
$response = $prism->withTools(['puppeteer', 'github'])
    ->withPrompt('Find Laravel on GitHub and take a screenshot')
    ->asText();
```

### Structured Output

Generate strongly-typed responses:

```php
use App\Services\PrismService;

$prism = new PrismService;

// Define schema as array
$schema = [
    'type' => 'object',
    'properties' => [
        'summary' => ['type' => 'string'],
        'points' => ['type' => 'array', 'items' => ['type' => 'string']],
    ],
    'required' => ['summary', 'points'],
];

$response = $prism->generateStructured(
    'Explain Laravel in 3 bullet points',
    $schema
);

// $response is now a strongly-typed array matching the schema
echo $response['summary'];
```

### Streaming

Stream responses in real-time:

```php
use App\Services\PrismService;

$prism = new PrismService;

$prism->text()
    ->withPrompt('Write a long story about Laravel')
    ->asStream(function (string $chunk): void {
        echo $chunk;
    });
```

### Example Command

Try the example command to test your setup:

```bash
# Basic usage
php artisan prism:example --prompt="Explain Laravel in one sentence"

# With streaming
php artisan prism:example --prompt="Tell me a story" --stream

# With MCP tools
php artisan prism:example --prompt="Navigate to laravel.com" --tools=puppeteer

# With structured output
php artisan prism:example --structured --schema='{"type":"object","properties":{"summary":{"type":"string"}}}' --prompt="Summarize Laravel"

# With different provider
php artisan prism:example --provider=anthropic --model=claude-3-5-sonnet-20241022 --prompt="Hello"
```

## MCP Integration with Relay

This starter kit includes [Prism Relay](https://github.com/prism-php/relay) for integrating MCP (Model Context Protocol) servers with Prism. Relay allows your AI agents to use external tools and APIs.

### Configuration

Relay is configured in `config/relay.php`. You can define MCP servers that provide tools for your AI agents:

```php
'servers' => [
    'puppeteer' => [
        'transport' => Transport::Stdio,
        'command' => ['npx', '-y', '@modelcontextprotocol/server-puppeteer'],
        'timeout' => env('RELAY_PUPPETEER_SERVER_TIMEOUT', 60),
        'env' => [],
    ],
],
```

### Using MCP Tools with Prism

You can use MCP tools in your Prism requests:

```php
use Prism\Prism\Enums\Provider;
use Prism\Prism\Facades\Prism;
use Prism\Relay\Facades\Relay;

$response = Prism::text()
    ->using(Provider::OpenRouter, 'openai/gpt-4o-mini')
    ->withPrompt('Find information about Laravel on the web')
    ->withTools(Relay::tools('puppeteer'))
    ->asText();
```

The agent can now use any tools provided by the configured MCP servers, such as navigating webpages, taking screenshots, clicking buttons, and more.

### Available Transports

Relay supports multiple transport mechanisms:

- **STDIO Transport**: For locally running MCP servers that communicate via standard I/O
- **HTTP Transport**: For MCP servers that communicate over HTTP

For more information, see the [Prism Relay documentation](https://github.com/prism-php/relay).

## Advanced Features

Prism supports many advanced features:

- **Structured Output**: Transform AI responses into strongly-typed data
- **Tool Calling**: Empower AI with custom tools and external APIs via Relay
- **Streaming**: Stream responses in real-time
- **Multi-Modal**: Work with text, images, and audio
- **Document Support**: Send PDFs and other documents to compatible models
- **MCP Integration**: Use MCP servers to extend AI capabilities with external tools

## Error Handling

Prism throws specific exceptions that you can catch and handle:

```php
use App\Services\PrismService;
use Prism\Prism\Exceptions\PrismException;
use Prism\Prism\Exceptions\PrismRateLimitedException;
use Prism\Prism\Exceptions\PrismRequestTooLargeException;
use Prism\Relay\Exceptions\RelayException;
use Prism\Relay\Exceptions\ServerConfigurationException;
use Prism\Relay\Exceptions\ToolCallException;

$prism = new PrismService;

try {
    $response = $prism->generate('Your prompt here');
    echo $response->text;
} catch (PrismRateLimitedException $e) {
    // Handle rate limiting
    Log::warning('Rate limited: '.$e->getMessage());
    // Retry with exponential backoff
} catch (PrismRequestTooLargeException $e) {
    // Handle request too large
    Log::error('Request too large: '.$e->getMessage());
    // Reduce prompt size or split request
} catch (PrismException $e) {
    // Handle other Prism errors
    Log::error('Prism error: '.$e->getMessage());
} catch (ServerConfigurationException $e) {
    // Handle MCP server configuration errors
    Log::error('MCP server configuration error: '.$e->getMessage());
} catch (ToolCallException $e) {
    // Handle tool call errors
    Log::error('Tool call error: '.$e->getMessage());
} catch (RelayException $e) {
    // Handle other Relay errors
    Log::error('Relay error: '.$e->getMessage());
} catch (\Exception $e) {
    // Handle any other errors
    Log::error('Unexpected error: '.$e->getMessage());
}
```

### Common Error Scenarios

**Missing API Key:**
```php
// Error: "API key is required"
// Solution: Set OPENROUTER_API_KEY in .env
```

**Invalid Model:**
```php
// Error: "Model not found"
// Solution: Check model name format (e.g., 'openai/gpt-4o-mini' for OpenRouter)
```

**MCP Server Not Configured:**
```php
// Error: "MCP server 'puppeteer' not found in configuration"
// Solution: Add server to config/relay.php
```

**Rate Limiting:**
```php
// Error: PrismRateLimitedException
// Solution: Implement retry logic with exponential backoff
```

## Configuration Validation

Validate your Prism and Relay configuration:

```bash
php artisan prism:validate
```

This command checks:
- Default provider and model configuration
- OpenRouter API key and URL
- MCP server configurations
- Tool availability from MCP servers

Use this command to troubleshoot configuration issues or verify your setup before using Prism in production.

For more information, see the [Prism PHP documentation](https://prismphp.com/).
