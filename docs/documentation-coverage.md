# Documentation coverage

**Short answer: docs do not cover 100% of the system.** The tooling and manifest track a defined subset; some areas are out of scope, and many tracked docs are still stubs.

## What `docs:sync` tracks (and considers “documented”)

- **Actions**: All PHP classes in `app/Actions` (including subfolders, e.g. `Fortify/`, `Billing/`). Each must have a file under `docs/developer/backend/actions/` and be marked in the manifest.
- **Controllers**: All PHP classes in `app/Http/Controllers` (including subfolders, e.g. `Dashboard/`, `Rakes/`, `Indents/`). Each must have a file under `docs/developer/backend/controllers/` and be marked in the manifest.
- **Pages**: All `.tsx` pages under `resources/js/pages`. Each must have a file under `docs/developer/frontend/pages/` and be marked in the manifest.

Run `php artisan docs:sync --check` to ensure every discovered Action, Controller, and Page has a doc and is marked documented. Use `php artisan docs:sync --generate` to create stubs for newly discovered items.

## What is not tracked by the manifest

- **Middleware** (e.g. `EnsureSidingAccess`, `tenant`)
- **MCP tools** (e.g. `SidingsIndexTool`, `UserSidingsTool`) – described in [developer/backend/mcp.md](developer/backend/mcp.md)
- **Models, migrations, jobs, events, listeners**
- **Config files** (e.g. `config/rrmcs.php`) – described in feature docs where relevant
- **Routes** – listed in [developer/api-reference/routes.md](developer/api-reference/routes.md) or via `php artisan route:list`
- **Form requests, API resources, policies** – no automatic manifest entries

So “100% documented” in the sense of `docs:sync --check` means: every discovered Action, Controller, and Page has a doc file. It does **not** mean every class, config, or route in the app has a doc.

## Stub vs full content

Many of the tracked docs were generated from templates and still contain placeholders (e.g. `{What this page allows...}`, `{One-line description...}`). The manifest only records that a doc **exists**; it does not check that the content is filled. Improving those docs is done by editing the markdown files (or using `docs:sync --generate --ai` if configured).

## Railway (RRMCS) docs

For a single overview of railway features and a list of railway-related docs, see [railway/rrmcs-features.md](railway/rrmcs-features.md) and [railway/README.md](railway/README.md).
