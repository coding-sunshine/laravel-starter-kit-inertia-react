# PostgreSQL + pgvector

This starter kit supports [pgvector](https://github.com/pgvector/pgvector) for vector embeddings when using PostgreSQL. Use it for semantic search, RAG, or any AI feature that stores or queries embeddings.

## Requirements

- PostgreSQL with the `vector` extension (e.g. [Laravel Herd Pro](https://herd.laravel.com) PostgreSQL, or install pgvector on your server).
- Set `DB_CONNECTION=pgsql` and configure `DB_*` in `.env`.

## Setup

The pgvector Laravel package is already installed. Migrations are included:

1. **Vector extension** – `2022_08_03_000000_create_vector_extension.php` creates the `vector` extension (skips when the driver is not `pgsql`).
2. **Demo table** – `*_create_embedding_demos_table.php` creates an `embedding_demos` table with a 3‑dimensional `embedding` column for demos and tests (also skips on non‑PostgreSQL).

Run migrations:

```bash
php artisan migrate
```

On SQLite (e.g. default test env), the vector and embedding_demos migrations no‑op so the test suite still passes.

## Usage

### Schema

In migrations, use the `vector` column type (and optional dimensions). The macro is registered by the pgvector package:

```php
Schema::create('documents', function (Blueprint $table) {
    $table->id();
    $table->string('content');
    $table->vector('embedding', 1536)->nullable(); // e.g. OpenAI embedding size
    $table->timestamps();
});
```

Migrations that use `vector` should run only on PostgreSQL (e.g. guard with `Schema::getConnection()->getDriverName() === 'pgsql'` or keep them in a separate stack).

### Model

Use the `HasNeighbors` trait and cast the vector column to `Pgvector\Laravel\Vector`:

```php
use Illuminate\Database\Eloquent\Model;
use Pgvector\Laravel\HasNeighbors;
use Pgvector\Laravel\Vector;

class Document extends Model
{
    use HasNeighbors;

    protected $fillable = ['content', 'embedding'];

    protected function casts(): array
    {
        return [
            'embedding' => Vector::class,
        ];
    }
}
```

### Storing embeddings

Assign a `Pgvector\Laravel\Vector` instance (or an array); the cast serializes it for PostgreSQL:

```php
use Pgvector\Laravel\Vector;

Document::create([
    'content' => 'Some text',
    'embedding' => new Vector([0.1, -0.2, 0.3, ...]),
]);
```

### Nearest-neighbor queries

Use `nearestNeighbors()` with a distance type:

```php
use Pgvector\Laravel\Distance;
use Pgvector\Laravel\Vector;

$query = [0.1, -0.2, 0.3];

$neighbors = Document::query()
    ->nearestNeighbors('embedding', $query, Distance::L2)
    ->take(10)
    ->get();

// Or with Cosine similarity (common for normalized embeddings)
$neighbors = Document::query()
    ->nearestNeighbors('embedding', $query, Distance::Cosine)
    ->take(10)
    ->get();
```

Each result includes `neighbor_distance` when using the scope. Supported distances: `L2`, `Cosine`, `InnerProduct`, `L1`, `Hamming`, `Jaccard`.

### Demo model and tests

The `App\Models\EmbeddingDemo` model and `embedding_demos` table are a minimal example. Feature tests in `tests/Feature/PgvectorTest.php` run only when the database driver is `pgsql`; they are skipped when using SQLite (default test DB).

To run pgvector tests, use PostgreSQL for tests (e.g. set `DB_CONNECTION=pgsql` and `DB_DATABASE=...` in `.env.testing` or `phpunit.xml`), then:

```bash
php artisan test --filter=Pgvector
```

## References

- [pgvector PHP](https://github.com/pgvector/pgvector-php) – Laravel integration and API
- [pgvector](https://github.com/pgvector/pgvector) – PostgreSQL extension
