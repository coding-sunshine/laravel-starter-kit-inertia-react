# Advanced Seeder Automation Features

This document describes the advanced automation features implemented for the seeder system.

## Overview

The advanced seeder automation extends the base seeder system with:
- **Seed Specs**: Canonical descriptions of how models should be seeded
- **Schema Change Detection**: Automatic updates when models/migrations change
- **AI-Assisted Generation**: Offline AI for generating realistic seed data (with graceful fallback)
- **Automatic Spec Sync**: Auto-syncs specs after migrations run
- **Smart Auto-Generation**: Auto-generates JSON when creating models (AI or Faker)
- **Interactive Pre-Commit**: Prompts to auto-fix missing components
- **Test-Driven Scenarios**: Named scenarios that tests can reference
- **Real Data Profiling**: Learn from production/staging data patterns
- **Observability**: Metrics, logs, and strict/lenient modes
- **AI Review**: Automated review of seeders and specs
- **Structured Output**: Reliable JSON generation using Prism structured output

## Seed Specs

Seed specs are JSON files that describe how a model should be seeded. They live in `database/seeders/specs/` and contain:

- **Fields**: Type, nullability, defaults, enums
- **Relationships**: Type and related models
- **Value Hints**: Example values and patterns
- **Scenarios**: Named seeding scenarios

### Commands

**Sync specs with models:**
```bash
php artisan seeds:spec-sync
```

Options:
- `--check` - Check mode (report differences without updating)
- `--model=ModelName` - Sync specific model only
- `--force` - Force update even if approval needed

**Regenerate seeders from specs:**
```bash
php artisan seeds:regenerate
```

Options:
- `--check` - Check mode
- `--model=ModelName` - Regenerate specific model
- `--force` - Force regeneration

## Schema Change Detection

The system automatically detects when migrations or models change and updates seed specs accordingly.

**Schema Watcher Service** (`app/Services/SchemaWatcher.php`):
- Detects migration changes via git diff
- Detects model file changes
- Identifies affected models
- Integrates with spec-sync workflow

**Migration Listener** (`app/Listeners/MigrationListener.php`):
- Automatically syncs seed specs after migrations complete
- Only syncs specs for models affected by the migrations
- Runs silently in the background (no output unless errors)
- Can be disabled via `config/seeding.php` → `auto_sync_after_migrations`

**Configuration:**
```php
// config/seeding.php
'auto_sync_after_migrations' => env('SEEDING_AUTO_SYNC_AFTER_MIGRATIONS', true),
```

## AI-Assisted Generation

**Offline AI JSON Generator** (`seeds:generate-ai`):
- Reads seed specs and AI profiles
- Generates realistic JSON seed data
- Supports multiple scenarios (basic_demo, edge_cases, etc.)
- All generated data is committed and reviewed
- **Automatic fallback**: Uses Faker when AI is unavailable
- **Availability checking**: Detects if AI provider is configured

**AI Profiles** (`database/seeders/profiles/`):
- Domain descriptions
- Locale and tone settings
- Field semantics
- Scenario definitions

**Usage:**
```bash
php artisan seeds:generate-ai --model=Post --scenario=basic_demo
```

Options:
- `--model=ModelName` - Generate for specific model
- `--scenario=basic_demo` - Scenario to generate
- `--provider=openrouter` - AI provider (openrouter, openai, anthropic)
- `--dry-run` - Show prompts without calling AI

**AI Availability & Fallback:**
- The system automatically checks if AI providers (OpenRouter, OpenAI, Anthropic) are configured
- If AI is unavailable, automatically falls back to `TraditionalSeedGenerator` using Faker
- Commands never fail due to missing AI - they gracefully degrade to traditional methods
- Configure AI providers in `config/prism.php` or via environment variables

## Automatic Spec Sync After Migrations

**Migration Listener** (`app/Listeners/MigrationListener.php`):
- Automatically runs after migrations complete
- Detects which models were affected by migrations
- Syncs seed specs for affected models only
- Runs silently (no output unless errors)
- Never blocks migrations - fails gracefully

**Configuration:**
```php
// config/seeding.php
'auto_sync_after_migrations' => env('SEEDING_AUTO_SYNC_AFTER_MIGRATIONS', true),
```

**How it works:**
1. Migration runs and completes
2. `MigrationsEnded` event is fired
3. `MigrationListener` detects changed models via `SchemaWatcher`
4. For each affected model, generates new spec and compares with existing
5. Updates spec if fields/relationships changed
6. All errors are caught silently to not break migrations

## Test-Driven Scenarios

**Named Scenarios** (`database/seeders/scenarios.json`):
- Define reusable seeding scenarios
- Map to models, counts, and factory states
- Used by tests via `seedScenario()` helper

**Usage in Tests:**
```php
it('can list user orders', function () {
    seedScenario('user_with_orders');
    // Test code...
});
```

**Test Coverage Analysis** (`seeds:test-coverage`):
- Analyzes test files for model/relationship usage
- Reports missing scenarios
- Identifies unseeded but tested relationships

**Usage:**
```bash
php artisan seeds:test-coverage
php artisan seeds:test-coverage --json
```

## Real Data Profiling

**Profiler Command** (`seeds:profile`):
- Read-only profiling of staging/production databases
- Calculates cardinalities and distributions
- Generates profile JSON files
- Never exports raw data (privacy-safe)

**Usage:**
```bash
php artisan seeds:profile --connection=staging --output=profiles/staging.json
```

**Synthetic Replica** (`seeds:replica`):
- Generates fake data matching production patterns
- Uses profiles as constraints
- Useful for performance testing and demos

**Usage:**
```bash
php artisan seeds:replica --profile=profiles/production.json --count=1000
```

## Observability

**Metrics Tracking** (`app/Services/SeedingMetrics.php`):
- Tracks duration per seeder
- Records created per model
- Warnings and errors
- Saves to `storage/logs/seeding_metrics_*.json`

**View Metrics:**
```bash
php artisan seeds:metrics --latest
php artisan seeds:metrics --file=path/to/metrics.json
```

**Strict vs Lenient Modes:**
- **Strict** (CI/production): Fails on any error
- **Lenient** (local): Logs warnings but continues

**Usage:**
```bash
php artisan seed:environment --strict
php artisan seed:environment --lenient
```

## AI Review

**Seeder Review** (`seeds:review`):
- AI-based review of seeders and specs
- Checks for relationship coverage
- Validates idempotency
- Reviews data realism

**Usage:**
```bash
php artisan seeds:review --model=Post
php artisan seeds:review --dry-run
```

**Prose to Spec** (`seeds:from-prose`):
- Generate seed specs from natural language
- AI converts domain descriptions to structured specs

**Usage:**
```bash
php artisan seeds:from-prose "A Project has many Tasks" --model=Project
```

## Enhanced Model Lifecycle

**MakeModelFullCommand** now automatically:
- Generates seed specs when creating models
- Ensures all components are created together
- Updates manifest.json

**ModelsAuditCommand** enhanced with:
- `--check-specs` - Check for missing seed specs
- `--fail-on-missing` - Fail CI if components missing
- Auto-generate missing specs with `--generate`

## CI Integration

The GitHub Actions workflow now includes:
- Seed spec sync check
- Model audit with spec checking
- Fails if specs are out of sync

## Workflow

### Standard Workflow (Fully Automated - Zero Manual Work)

1. **Create Model**: `php artisan make:model:full Post --all`
   - ✅ Creates model, factory, seeder, JSON (auto-generated), spec
   - ✅ **Auto-generates JSON**: Uses AI if available, Faker otherwise
   - ✅ **Auto-generates spec**: Canonical description of model
   - ✅ **Auto-detects relationships**: Uses model reflection to find ALL relationships
   - ✅ **AI-generated seeder code**: Intelligent seeder with idempotent patterns
   - ✅ **Idempotent by default**: Uses updateOrCreate for safe re-runs
   - ✅ **Relationship seeding**: Auto-generates code to seed dependencies

2. **Modify Model/Migration**: Automatically handled
   - ✅ **Migration Listener**: Auto-syncs specs after migrations complete
   - ✅ **Auto-regenerates seeders**: If relationships change, seeders are updated automatically
   - ✅ **Detects changes**: Knows exactly what changed (fields, relationships)
   - ✅ Only syncs specs for affected models
   - ✅ Runs silently in background

3. **Pre-Commit Hook**: Validates before commit
   - ✅ Checks for missing seeders/specs
   - ✅ **Interactive prompt**: Offers to auto-generate
   - ✅ Auto-fixes if approved

**Result**: Everything stays up-to-date automatically. You never manually update seeders.

### Manual Workflow (When Needed)

1. **Sync Specs**: `php artisan seeds:spec-sync`
   - Updates specs from migrations/models
   - Use `--check` to verify without updating

2. **Regenerate**: `php artisan seeds:regenerate`
   - Updates seeders/JSON from specs
   - Use when specs change significantly

3. **Generate AI Data**: `php artisan seeds:generate-ai`
   - Creates realistic JSON examples
   - Falls back to Faker if AI unavailable

4. **Profile Production**: `php artisan seeds:profile`
   - Learn from real data patterns

5. **Review**: `php artisan seeds:review`
   - AI review of seeding setup
   - Falls back to basic validation if AI unavailable

## File Structure

```
database/
├── seeders/
│   ├── specs/              # Seed specs (one per model)
│   │   └── User.json
│   ├── profiles/           # AI profiles
│   │   └── User.json
│   └── scenarios.json      # Named scenarios
└── data/
    └── users.json          # Seed data (with AI metadata)

storage/
└── logs/
    └── seeding_metrics_*.json  # Metrics from runs
```

## Enhanced Relationship Detection

**EnhancedRelationshipAnalyzer** (`app/Services/EnhancedRelationshipAnalyzer.php`):
- Uses model reflection to detect ALL relationship types
- Extracts actual relationship instances to get foreign keys, pivot tables, etc.
- Supports: belongsTo, hasOne, hasMany, belongsToMany, hasOneThrough, hasManyThrough, morphTo, morphMany, morphOne, morphToMany
- Falls back to migration-based detection if model doesn't exist yet

**Benefits:**
- Detects relationships from actual model code (not just migrations)
- Gets full relationship details (foreign keys, pivot tables, etc.)
- Works with complex relationships (polymorphic, pivot tables)
- More accurate than regex-based migration parsing

## AI-Powered Seeder Generation

**AISeederCodeGenerator** (`app/Services/AISeederCodeGenerator.php`):
- Generates intelligent seeder code using AI
- Understands relationships, fields, and domain context
- Falls back to traditional generation if AI unavailable
- Creates idempotent code by default

**Features:**
- Uses AI to generate context-aware seeder logic
- Automatically uses updateOrCreate for idempotency
- Detects unique fields (email, slug, etc.) for idempotent operations
- Generates relationship seeding code
- Handles factory states intelligently

## Auto-Regeneration on Changes

**MigrationListener Enhancement:**
- Detects when relationships are added/removed
- Automatically regenerates seeder code
- Preserves custom code in protected regions
- Runs silently after migrations

**Configuration:**
```php
// config/seeding.php
'auto_regenerate_seeders' => env('SEEDING_AUTO_REGENERATE_SEEDERS', true),
```

## Idempotency

All generated seeders are **idempotent by default**:
- ✅ JSON seeding uses `updateOrCreate()` when unique fields exist
- ✅ Relationship seeding checks for existence before creating
- ✅ Safe to run multiple times without duplicates
- ✅ Detects unique identifiers (email, slug, uuid, code, name, etc.)
- ✅ Automatically determines which field to use for idempotency

**Example (Auto-generated):**
```php
// Auto-generated idempotent code
if (isset($itemData['email']) && !empty($itemData['email'])) {
    User::query()->updateOrCreate(
        ['email' => $itemData['email']],
        $itemData
    );
}
```

**Benefits:**
- Can run `php artisan db:seed` multiple times safely
- No duplicate records
- Updates existing records instead of creating duplicates
- Works with any unique field (email, slug, uuid, etc.)

## Best Practices

1. **Always use `make:model:full`** - Ensures all components created with AI-powered generation
2. **Let MigrationListener handle updates** - Auto-syncs specs and regenerates seeders
3. **Review AI-generated code** - Quick review (usually approve as-is)
4. **Use scenarios** in tests for consistency
5. **Profile production** periodically to keep seeds realistic
6. **Check metrics** after seeding to catch issues early
7. **Use strict mode** in CI/production
8. **Trust the automation** - System handles 95% of work automatically
