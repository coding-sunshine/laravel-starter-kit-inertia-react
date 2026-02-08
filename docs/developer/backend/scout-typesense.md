# Scout + Typesense (full-text search)

Full-text search is provided by **Laravel Scout** with the **Typesense** driver. Typesense can be run locally (e.g. via [Laravel Herd](https://herd.laravel.com)) or hosted (Typesense Cloud).

## Configuration

- **Config**: `config/scout.php` — driver (`SCOUT_DRIVER`), Typesense client settings, and per-model collection schemas.
- **Environment** (`.env`):
  - `SCOUT_DRIVER=typesense` — use Typesense (use `collection` or `database` for no external server).
  - `TYPESENSE_API_KEY` — API key (Laravel Herd uses `LARAVEL-HERD`).
  - `TYPESENSE_HOST` — host (e.g. `localhost` for Herd).
  - `TYPESENSE_PORT` — optional, default `8108` (Herd).
  - Optional: `TYPESENSE_PATH`, `TYPESENSE_PROTOCOL` (default `http`).

When `SCOUT_DRIVER` is not set or is `collection`, Scout uses the in-memory collection driver and no Typesense server is required.

## Searchable models

- **User**: `App\Models\User` uses the `Searchable` trait; indexable fields are `id`, `name`, `email`, `created_at`. Collection schema and `query_by` are in `config/scout.php` under `typesense.model-settings`.

For Typesense, `toSearchableArray()` must return `id` as string and `created_at` as UNIX timestamp (int64). Define the collection schema for each model in `config/scout.php` under `typesense.model-settings`.

## Commands

- `php artisan scout:import "App\Models\User"` — import existing records into Typesense (run after enabling Typesense or adding a new searchable model).
- `php artisan scout:flush "App\Models\User"` — remove all User documents from the index.
- `php artisan scout:sync-index-settings` — sync collection/index settings (when supported).

## Usage

```php
use App\Models\User;

// Search by query string (searches name, email per config)
$users = User::search('john')->get();

// Paginate
$users = User::search('john')->paginate(15);

// Optional: dynamic search parameters
User::search('john')->options(['query_by' => 'name,email'])->get();
```

## Laravel Herd

Herd includes Typesense. Use:

- `SCOUT_DRIVER=typesense`
- `TYPESENSE_API_KEY=LARAVEL-HERD`
- `TYPESENSE_HOST=localhost`
- `TYPESENSE_PORT=8108` (default in config)

## References

- [Laravel Scout](https://laravel.com/docs/scout) — installation, drivers, indexing, searching.
- [Typesense](https://typesense.org/docs/) — schema, search parameters.
- Config: `config/scout.php` — `typesense.client-settings`, `typesense.model-settings`.
