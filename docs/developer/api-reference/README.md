# API Reference

This section documents all routes and endpoints available in the application.

## API versioning

The public API is versioned under **`/api/v1/`**. Use this prefix for all API consumers. The root `/api` returns a short info message; the versioned base is `/api/v1`.

## API response format

Success and error responses follow the **essa/api-tool-kit** format when the request expects JSON (`Accept: application/json` or equivalent):

- **Success (e.g. show, store, batch)**: `{ "status": 200, "message": "...", "data": ... }`
- **Created**: `{ "status": 201, "message": "...", "data": ... }`
- **Deleted**: HTTP 204 No Content (empty body)
- **Validation errors**: `application/problem+json` with an `errors` array (status 422)
- **Other errors**: `application/problem+json` with `errors[{ status, title, detail }]`

List endpoints (index, search) return Laravel API Resource format: `{ "data": [ ... ], "links": ..., "meta": ... }` (paginated).

## Routes

- **Web**: `routes/web.php`
- **API**: `routes/api.php` (prefixed with `/api`; v1 routes under `/api/v1`; documented by [Scramble](../backend/scramble.md) at `/docs/api`)

For the complete list of routes, see [Routes Documentation](./routes.md).

## Integration guides

- **[Railway Receipt import preview (multipart)](./railway-receipt-import-preview-api.md)** — Developer copy; same specification as **[mobile handoff: `docs/mobile/railway-receipt-import-preview-api.md`](../../mobile/railway-receipt-import-preview-api.md)** (`POST /api/v1/railway-receipts/import-preview`): auth, multipart `pdf`, success and all error scenarios. Success responses return a plain JSON body (fields at top level), not necessarily the **`essa/api-toolkit`** `{ status, message, data }` envelope used by many other JSON endpoints — see each guide.
- **[Dashboard siding overview & performance metrics](./dashboard-siding-performance.md)** — Filters (including `sp_*` split-chart overrides), date formats, and success/error responses for the dashboard siding endpoints used by web and mobile.
- **[Dashboard rake-wise performance](./dashboard-rake-performance.md)** — Web + mobile rake performance routes and legacy monolithic mobile endpoint.
- **[Mobile rake performance rakes API](./mobile-rake-performance-rakes-api.md)** — Only `GET /api/v1/dashboard/rake-performance/rakes` and `GET /api/v1/dashboard/rake-performance/rakes/{rake}`: every query parameter (names and formats), success JSON, and HTTP errors.

## Filter, sort, include, and fields (v1 list endpoints)

List endpoints that use **spatie/laravel-query-builder** support:

- **filter**: `filter[name]=value`, `filter[email]=value` (partial match by default)
- **sort**: `sort=name`, `sort=-created_at` (minus for descending)
- **include**: `include=roles` (e.g. for users index)
- **fields**: `fields[users]=id,name,email` — sparse fieldsets; only requested attributes are returned. Allowed user fields: `id`, `name`, `email`, `email_verified_at`, `created_at`, `updated_at`. Use with list and show where applicable.

See [Scramble](../backend/scramble.md) and the controller docblocks for allowed parameters per endpoint.

> **Note**: Route documentation is regenerated with `php artisan docs:api` when routes are added or modified.
