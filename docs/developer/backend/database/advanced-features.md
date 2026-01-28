# Advanced Seeder Automation Features

This document describes the advanced automation features implemented for the seeder system.

## Overview

The advanced seeder automation extends the base seeder system with:
- **Seed Specs**: Canonical descriptions of how models should be seeded
- **Schema Change Detection**: Automatic updates when models/migrations change
- **AI-Assisted Generation**: Offline AI for generating realistic seed data
- **Test-Driven Scenarios**: Named scenarios that tests can reference
- **Real Data Profiling**: Learn from production/staging data patterns
- **Observability**: Metrics, logs, and strict/lenient modes
- **AI Review**: Automated review of seeders and specs

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

## AI-Assisted Generation

**Offline AI JSON Generator** (`seeds:generate-ai`):
- Reads seed specs and AI profiles
- Generates realistic JSON seed data
- Supports multiple scenarios (basic_demo, edge_cases, etc.)
- All generated data is committed and reviewed

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
- `--provider=local` - AI provider (openai, anthropic, local)
- `--dry-run` - Show prompts without calling AI

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

1. **Create Model**: `php artisan make:model:full Post --all`
   - Creates model, factory, seeder, JSON, spec

2. **Sync Specs**: `php artisan seeds:spec-sync`
   - Updates specs from migrations/models

3. **Regenerate**: `php artisan seeds:regenerate`
   - Updates seeders/JSON from specs

4. **Generate AI Data**: `php artisan seeds:generate-ai`
   - Creates realistic JSON examples

5. **Profile Production**: `php artisan seeds:profile`
   - Learn from real data patterns

6. **Review**: `php artisan seeds:review`
   - AI review of seeding setup

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

## Best Practices

1. **Always use `make:model:full`** - Ensures all components created
2. **Run `seeds:spec-sync`** after schema changes
3. **Review AI-generated data** before committing
4. **Use scenarios** in tests for consistency
5. **Profile production** periodically to keep seeds realistic
6. **Check metrics** after seeding to catch issues early
7. **Use strict mode** in CI/production
