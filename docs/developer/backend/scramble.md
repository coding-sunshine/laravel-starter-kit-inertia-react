# Scramble OpenAPI Documentation

The application uses [Scramble](https://scramble.dedoc.co/) to generate OpenAPI (Swagger) documentation from your API routes. No manual annotations are required.

## Access

- **UI**: `/docs/api` (local environment only by default)
- **OpenAPI JSON**: `/docs/api.json`

Routes under the `api` path are included automatically. See [API Reference](../api-reference/routes.md) for the full route list.

## Configuration

- **Config**: `config/scramble.php`
- **API path**: `scramble.api_path` (default `api`) — only routes starting with this path are documented
- **API version**: `API_VERSION` in `.env` or `scramble.info.version`
- **Access**: Docs are restricted to the `local` environment unless you define the `viewApiDocs` gate (e.g. in `AppServiceProvider`) for other environments

## Adding API Routes

Define routes in `routes/api.php`. They are prefixed with `/api` and appear in the Scramble docs automatically. Use controllers and form requests as usual; Scramble infers request/response shapes from your code.

## Customization

- **Overview text**: Set `scramble.info.description` in config (Markdown supported)
- **Theme**: `scramble.ui.theme` — `light`, `dark`, or `system`
- **Custom docs URL**: Use `Scramble::configure()->expose(ui: '…', document: '…')` in a service provider
- **Exclude routes**: Add `Dedoc\Scramble\Attributes\ExcludeRouteFromDocs` on a controller method, or `ExcludeAllRoutesFromDocs` on a controller

## Export

The OpenAPI spec can be exported; see `scramble.export_path` and the [Scramble export docs](https://scramble.dedoc.co/usage/export).
