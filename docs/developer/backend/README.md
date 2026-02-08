# Backend Documentation

Backend components, services, and patterns for developers.

**At a glance (for agents):** Public API is versioned at **`/api/v1/`** (see [API reference](../api-reference/README.md)); list endpoints use **spatie/laravel-query-builder** (filter/sort/include); success/error shape uses **essa/api-tool-kit**. **MCP** server at `POST /mcp/api` (auth:sanctum) exposes tools `users_index`, `users_show` (see [mcp.md](./mcp.md)). **Content & export:** Tags on User (spatie/laravel-tags), profile PDF at `profile.export-pdf`, Filament User export (XLSX/CSV) — see [content-export.md](./content-export.md). Database-backed **settings** live in `App\Settings\*` and are edited in Filament under the **Settings** group (App, Auth, SEO). **Feature flags** are in `config/feature-flags.php` and shared to Inertia as the `features` prop. **Response cache** applies to guest GET only (see [response-cache.md](./response-cache.md)). **SEO & monitoring:** Sitemap (`sitemap:generate`, daily), robots.txt route, legal pages (`/legal/terms`, `/legal/privacy`), Sentry, GA4 (spatie/laravel-analytics), Slack webhook (failed-job alerts) — see [seo-and-monitoring.md](./seo-and-monitoring.md). **Backups:** spatie/laravel-backup, scheduled daily (`backup:run` then `backup:clean`) — [backup.md](./backup.md). **Third-party APIs:** use Saloon; connectors in `App\Http\Integrations\*`, example in [saloon.md](./saloon.md). **Userstamps:** wildside/userstamps (`created_by`/`updated_by`), see [userstamps.md](./userstamps.md). **ADRs:** architecture decisions in [docs/architecture/ADRs/](../../architecture/ADRs/README.md).

## Contents

- [Actions](./actions/README.md) - Action classes and patterns
- [Activity Log](./activity-log.md) - Spatie and Filament activity logging
- [Backup & Restore](./backup.md) - spatie/laravel-backup, schedule, restore
- [Saloon](./saloon.md) - HTTP client for third-party APIs (connectors, requests)
- [Userstamps](./userstamps.md) - created_by / updated_by with wildside/userstamps
- [Controllers](./controllers/README.md) - Controller documentation (web and API v1)
- [Content & export](./content-export.md) - Tags (User), profile PDF, Filament Excel/CSV export
- [Database](./database/README.md) - Database patterns, seeders, and factories
- [Search & Data](./search-and-data.md) - DTOs, Sluggable, Sortable, Model Flags, Schemaless Attributes, Model States, Soft Cascade
- [Filament Admin Panel](./filament.md) - Filament panel at `/admin`
- [Feature Flags](./feature-flags.md) - Laravel Pennant, Filament plugin, Inertia shared props
- [Media Library (User avatar)](./media-library.md) - Spatie Media Library and user avatar (conversions, profile)
- [Permissions and RBAC](./permissions.md) - Route-based permissions, permission categories, role hierarchy
- [Prism AI Integration](./prism.md) - AI integration with Prism and OpenRouter
- [Laravel AI SDK](./ai-sdk.md) - Agents, embeddings, images, and when to use vs Prism
- [PostgreSQL + pgvector](./pgvector.md) - Vector embeddings with pgvector (optional)
- [Response Cache](./response-cache.md) - Guest GET response caching (exclude auth/admin)
- [Scramble OpenAPI Docs](./scramble.md) - OpenAPI/Swagger docs at `/docs/api`
- [MCP Server](./mcp.md) - Model Context Protocol server and tools (users_index, users_show); auth via Sanctum
- [SEO & Monitoring](./seo-and-monitoring.md) - Sitemap, robots.txt, legal pages, Sentry, GA4, Slack notifications
- [Settings](./settings.md) - Database-backed settings (app/auth/SEO), Filament Settings pages

## Quick Links

- [Actions Documentation](./actions/README.md) - All Action classes
- [Activity Log](./activity-log.md) - User and model activity logging
- [API versioning & list endpoints](../api-reference/README.md) - Public API at `/api/v1/`, filter/sort/include
- [Backup & Restore](./backup.md) - spatie/laravel-backup; schedule, commands, restore
- [Feature Flags](./feature-flags.md) - Pennant + Filament; expose to Inertia via `features` prop
- [Response Cache](./response-cache.md) - Guest GET cache; exclude auth/admin
- [Saloon](./saloon.md) - HTTP client for third-party APIs; connectors in `App\Http\Integrations\*`
- [Settings](./settings.md) - DB-backed settings; `App\Settings\*`; Filament Settings group
- [Userstamps](./userstamps.md) - created_by/updated_by with wildside/userstamps
- [Seeder System](./database/seeders.md) - Automated seeder system
- [Prism AI Integration](./prism.md) - AI-powered features with Prism (OpenRouter, commands)
- [Laravel AI SDK](./ai-sdk.md) - Agents, embeddings, media; use with Prism as needed
- [PostgreSQL + pgvector](./pgvector.md) - Vector embeddings (optional)
- [Scramble OpenAPI Docs](./scramble.md) - API documentation at `/docs/api`
- [Architecture Decision Records](../../architecture/ADRs/README.md) - ADRs in docs/architecture/ADRs/
