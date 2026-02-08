# Saloon HTTP Client

Third-party API integrations use **Saloon** (`saloonphp/saloon` v3) for a consistent, testable HTTP client layer.

## Where integrations live

- **Connectors**: `App\Http\Integrations\{ApiName}\{ApiName}Connector.php` — base URL, default headers, optional auth.
- **Requests**: `App\Http\Integrations\{ApiName}\Requests\*.php` — one class per endpoint (method + path).

## Example (included)

An example integration targets the public [JSONPlaceholder](https://jsonplaceholder.typicode.com) API:

- **Connector**: `App\Http\Integrations\ExampleApi\ExampleApiConnector`
- **Request**: `App\Http\Integrations\ExampleApi\Requests\GetPostRequest`

Usage:

```php
use App\Http\Integrations\ExampleApi\ExampleApiConnector;
use App\Http\Integrations\ExampleApi\Requests\GetPostRequest;

$connector = new ExampleApiConnector;
$response = $connector->send(new GetPostRequest(1));

$response->successful(); // true/false
$data = $response->json(); // decoded JSON
```

Base URL is configurable via `config('services.example_api.url')` (default: `https://jsonplaceholder.typicode.com`). Optional env: `EXAMPLE_API_URL`.

## Adding a new integration

1. Create a connector under `app/Http/Integrations/{Name}/` extending `Saloon\Http\Connector`, implementing `resolveBaseUrl()` and optionally `defaultHeaders()` / `defaultAuth()`.
2. Create request classes under `app/Http/Integrations/{Name}/Requests/` extending `Saloon\Http\Request`, setting `$method` and `resolveEndpoint()`.
3. Add any API base URL or keys to `config/services.php` and `.env.example` (never commit secrets).
4. Use the connector in Actions or jobs; prefer dependency injection for testability.

## Testing

- Use Saloon’s `FakeResponse` or `MockClient` to avoid real HTTP calls in tests.
- See [Saloon testing docs](https://docs.saloon.dev/testing/overview) for mocking and fixtures.

## References

- [Saloon v3 docs](https://docs.saloon.dev)
- Config: `config/services.php` → `example_api` (and your API keys as needed)
