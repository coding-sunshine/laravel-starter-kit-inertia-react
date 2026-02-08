# MCP (Model Context Protocol) Server

The application exposes an **MCP server** so AI clients can call API-like operations (list users, show user) via the [Model Context Protocol](https://modelcontextprotocol.io/).

## Endpoint and auth

- **URL**: `POST /mcp/api` (see `routes/ai.php`)
- **Auth**: Same as API routes — **Sanctum**. Clients must send a valid Bearer token (e.g. `Authorization: Bearer <token>`) or use session-based auth so the request is authenticated. The route uses `auth:sanctum` middleware.

## Registered server and tools

- **Server class**: `App\Mcp\Servers\ApiServer`
- **Tools**:
  - **users_index** — List users with optional filters (`filter_name`, `filter_email`), `sort`, `per_page`, `include` (e.g. `roles`). Returns paginated user data in the same shape as `GET /api/v1/users`.
  - **users_show** — Get one user by ID. Parameter: `id` (integer). Returns the same shape as `GET /api/v1/users/{id}`.

## Adding new tools

1. Create a tool class in `app/Mcp/Tools/` extending `Laravel\Mcp\Server\Tool`.
2. Implement `name()`, `description`, `schema(JsonSchema $schema)` (input params), and `handle(Request $request): Response`.
3. Register the class in `App\Mcp\Servers\ApiServer::$tools`.

See [Laravel MCP documentation](https://laravel.com/docs/mcp) for transport options (HTTP vs stdio) and tool schema conventions.

## Related

- **API**: [API reference](../api-reference/README.md) — same data is available via REST at `/api/v1/users`.
- **Routes**: MCP web route is defined in `routes/ai.php`.
