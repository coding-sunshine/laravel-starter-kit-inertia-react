<laravel-boost-guidelines>
=== .ai/app.actions rules ===

# App/Actions guidelines

- This application uses the Action pattern and prefers for much logic to live in reusable and composable Action classes.
- Actions live in `app/Actions`, they are named based on what they do, with no suffix.
- Actions will be called from many different places: jobs, commands, HTTP requests, API requests, MCP requests, and more.
- Create dedicated Action classes for business logic with a single `handle()` method.
- Inject dependencies via constructor using private properties.
- Create new actions with `php artisan make:action "{name}" --no-interaction`
- Wrap complex operations in `DB::transaction()` within actions when multiple models are involved.
- Some actions won't require dependencies via `__construct` and they can use just the `handle()` method.

<!-- Example action class -->
```php
<?php

declare(strict_types=1);

namespace App\Actions;

final readonly class CreateFavorite
{
    public function __construct(private FavoriteService $favorites)
    {
        //
    }

    public function handle(User $user, string $favorite): bool
    {
        return $this->favorites->add($user, $favorite);
    }
}
```

=== .ai/documentation rules ===

# Documentation Guidelines

## When to Document

Document new Actions, Controllers, Pages, and Routes. Skip documentation for bug fixes, refactors, and UI-only changes unless they change workflows.

## Documentation Locations

| Component | Location |
|-----------|----------|
| Actions | `docs/developer/backend/actions/` |
| Controllers | `docs/developer/backend/controllers/` |
| Pages | `docs/developer/frontend/pages/` |
| Routes | `docs/developer/api-reference/routes.md` |
| User-facing features | `docs/user-guide/` |

## Templates

Templates in `docs/.templates/`: `action.md`, `controller.md`, `page.md`, `user-feature.md`.

## Manifest Sync

Run `php artisan docs:sync` to update `docs/.manifest.json` with codebase state and relationships.

- `--check`: Check for undocumented items without updating
- `--generate`: Create documentation stubs for undocumented items

## Cross-Referencing

The manifest tracks relationships automatically:

- **Actions**: Which controllers use them, which models they use, which routes call them
- **Controllers**: Which actions they use, which form requests, which routes, which pages they render
- **Pages**: Which controllers render them, which routes lead to them

=== .ai/general rules ===

# General Guidelines

- Don't include any superfluous PHP Annotations, except ones that start with `@` for typing variables.

=== .ai/settings rules ===

# Settings & Configuration Guidelines

- Runtime configuration is DB-backed via **spatie/laravel-settings**. Settings classes live in `app/Settings/` and are managed via Filament admin pages.
- Never use `env()` outside of config files. Use `config('key')` — the `SettingsOverlayServiceProvider` writes DB values into config at boot.
- When adding a new setting: create the Settings class, create a settings migration in `database/settings/`, add the mapping to `SettingsOverlayServiceProvider::OVERLAY_MAP`, and create a Filament `SettingsPage`.
- Settings that hold secrets (API keys, passwords) must define `public static function encrypted(): array` returning the encrypted property names.
- 7 groups are org-overridable (Billing, Mail, Stripe, Paddle, LemonSqueezy, Prism, AI). Set `'orgOverridable' => true` in `OVERLAY_MAP` to enable per-org overrides.
- Per-org overrides are stored in the `organization_settings` table and applied by `ApplyOrganizationSettings` middleware after `SetTenantContext`.
- Infrastructure settings (`APP_KEY`, `DB_*`, `CACHE_STORE`, `SESSION_DRIVER`, `QUEUE_CONNECTION`, `REDIS_*`, `LOG_CHANNEL`) stay in `.env` — they are needed before the DB is available.
- See `docs/developer/backend/settings.md` for full documentation.

=== foundation rules ===

# Laravel Boost Guidelines

The Laravel Boost guidelines are specifically curated by Laravel maintainers for this application. These guidelines should be followed closely to ensure the best experience when building Laravel applications.

## Foundational Context

This application is a Laravel application and its main Laravel ecosystems package & versions are below. You are an expert with them all. Ensure you abide by these specific packages & versions.

- php - 8.4
- filament/filament (FILAMENT) - v5
- inertiajs/inertia-laravel (INERTIA_LARAVEL) - v3
- laravel/ai (AI) - v0
- laravel/fortify (FORTIFY) - v1
- laravel/framework (LARAVEL) - v13
- laravel/horizon (HORIZON) - v5
- laravel/mcp (MCP) - v0
- laravel/pennant (PENNANT) - v1
- laravel/prompts (PROMPTS) - v0
- laravel/pulse (PULSE) - v1
- laravel/reverb (REVERB) - v1
- laravel/sanctum (SANCTUM) - v4
- laravel/scout (SCOUT) - v11
- laravel/socialite (SOCIALITE) - v5
- laravel/wayfinder (WAYFINDER) - v0
- livewire/livewire (LIVEWIRE) - v4
- larastan/larastan (LARASTAN) - v3
- laravel/boost (BOOST) - v2
- laravel/pail (PAIL) - v1
- laravel/pint (PINT) - v1
- pestphp/pest (PEST) - v4
- phpunit/phpunit (PHPUNIT) - v12
- rector/rector (RECTOR) - v2
- \@inertiajs/react (INERTIA_REACT) - v3
- laravel-echo (ECHO) - v2
- react (REACT) - v19
- tailwindcss (TAILWINDCSS) - v4
- \@laravel/vite-plugin-wayfinder (WAYFINDER_VITE) - v0
- eslint (ESLINT) - v10
- prettier (PRETTIER) - v3

## Skills Activation

This project has domain-specific skills available. You MUST activate the relevant skill whenever you work in that domain—don't wait until you're stuck.

- `ai-sdk-development` — Builds AI agents, generates text and chat responses, produces images, synthesizes audio, transcribes speech, generates vector embeddings, reranks documents, and manages files and vector stores using the Laravel AI SDK (laravel/ai). Supports structured output, streaming, tools, conversation memory, middleware, queueing, broadcasting, and provider failover. Use when building, editing, updating, debugging, or testing any AI functionality, including agents, LLMs, chatbots, text generation, image generation, audio, transcription, embeddings, RAG, similarity search, vector stores, prompting, structured output, or any AI provider (OpenAI, Anthropic, Gemini, Cohere, Groq, xAI, ElevenLabs, Jina, OpenRouter).
- `fortify-development` — ACTIVATE when the user works on authentication in Laravel. This includes login, registration, password reset, email verification, two-factor authentication (2FA/TOTP/QR codes/recovery codes), profile updates, password confirmation, or any auth-related routes and controllers. Activate when the user mentions Fortify, auth, authentication, login, register, signup, forgot password, verify email, 2FA, or references app/Actions/Fortify/, CreateNewUser, UpdateUserProfileInformation, FortifyServiceProvider, config/fortify.php, or auth guards. Fortify is the frontend-agnostic authentication backend for Laravel that registers all auth routes and controllers. Also activate when building SPA or headless authentication, customizing login redirects, overriding response contracts like LoginResponse, or configuring login throttling. Do NOT activate for Laravel Passport (OAuth2 API tokens), Socialite (OAuth social login), or non-auth Laravel features.
- `laravel-best-practices` — Apply this skill whenever writing, reviewing, or refactoring Laravel PHP code. This includes creating or modifying controllers, models, migrations, form requests, policies, jobs, scheduled commands, service classes, and Eloquent queries. Triggers for N+1 and query performance issues, caching strategies, authorization and security patterns, validation, error handling, queue and job configuration, route definitions, and architectural decisions. Also use for Laravel code reviews and refactoring existing Laravel code to follow best practices. Covers any task involving Laravel backend PHP code patterns.
- `configuring-horizon` — Use this skill whenever the user mentions Horizon by name in a Laravel context. Covers the full Horizon lifecycle: installing Horizon (horizon:install, Sail setup), configuring config/horizon.php (supervisor blocks, queue assignments, balancing strategies, minProcesses/maxProcesses), fixing the dashboard (authorization via Gate::define viewHorizon, blank metrics, horizon:snapshot scheduling), and troubleshooting production issues (worker crashes, timeout chain ordering, LongWaitDetected notifications, waits config). Also covers job tagging and silencing. Do not use for generic Laravel queues without Horizon, SQS or database drivers, standalone Redis setup, Linux supervisord, Telescope, or job batching.
- `mcp-development` — Develops MCP servers, tools, resources, and prompts. Activates when creating MCP tools, resources, or prompts; setting up AI integrations; debugging MCP connections; working with routes/ai.php; or when the user mentions MCP, Model Context Protocol, AI tools, AI server, or building tools for AI assistants.
- `pennant-development` — Manages feature flags with Laravel Pennant. Activates when creating, checking, or toggling feature flags; showing or hiding features conditionally; implementing A/B testing; working with @feature directive; or when the user mentions feature flags, feature toggles, Pennant, conditional features, rollouts, or gradually enabling features.
- `pulse-development` — Handles Laravel Pulse setup, configuration, and custom card development. Activates when installing Pulse; configuring the dashboard or authorization gate; setting up recorders and filtering; building custom Livewire cards; optimizing with Redis ingest or sampling; or when the user mentions /pulse, pulse:check, pulse:work, Pulse::record(), or application monitoring.
- `scout-development` — Develops full-text search with Laravel Scout. Activates when installing or configuring Scout; choosing a search engine (Algolia, Meilisearch, Typesense, Database, Collection); adding the Searchable trait to models; customizing toSearchableArray or searchableAs; importing or flushing search indexes; writing search queries with where clauses, pagination, or soft deletes; configuring index settings; troubleshooting search results; or when the user mentions Scout, full-text search, search indexing, or search engines in a Laravel project. Make sure to use this skill whenever the user works with search functionality in Laravel, even if they don't explicitly mention Scout.
- `socialite-development` — Manages OAuth social authentication with Laravel Socialite. Activate when adding social login providers; configuring OAuth redirect/callback flows; retrieving authenticated user details; customizing scopes or parameters; setting up community providers; testing with Socialite fakes; or when the user mentions social login, OAuth, Socialite, or third-party authentication.
- `wayfinder-development` — Activates whenever referencing backend routes in frontend components. Use when importing from @/actions or @/routes, calling Laravel routes from TypeScript, or working with Wayfinder route functions.
- `pest-testing` — Tests applications using the Pest 4 PHP framework. Activates when writing tests, creating unit or feature tests, adding assertions, testing Livewire components, browser testing, debugging test failures, working with datasets or mocking; or when the user mentions test, spec, TDD, expects, assertion, coverage, or needs to verify functionality works.
- `inertia-react-development` — Develops Inertia.js v2 React client-side applications. Activates when creating React pages, forms, or navigation; using <Link>, <Form>, useForm, or router; working with deferred props, prefetching, or polling; or when user mentions React with Inertia, React pages, React forms, or React navigation.
- `echo-development` — Develops real-time broadcasting with Laravel Echo. Activates when setting up broadcasting (Reverb, Pusher, Ably); creating ShouldBroadcast events; defining broadcast channels (public, private, presence, encrypted); authorizing channels; configuring Echo; listening for events; implementing client events (whisper); setting up model broadcasting; broadcasting notifications; or when the user mentions broadcasting, Echo, WebSockets, real-time events, Reverb, or presence channels.
- `tailwindcss-development` — Styles applications using Tailwind CSS v4 utilities. Activates when adding styles, restyling components, working with gradients, spacing, layout, flex, grid, responsive design, dark mode, colors, typography, or borders; or when the user mentions CSS, styling, classes, Tailwind, restyle, hero section, cards, buttons, or any visual/UI changes.
- `developing-with-prism` — Guide for the narrow Prism/Relay role in this project. Activate ONLY when working with MCP tool integration via `PrismService::withTools()` (Relay bridge). For all other AI features — text generation, structured output, images, audio, embeddings, streaming, tools, conversation memory — use `developing-with-ai-sdk` instead.
- `database-mail` — Database-backed email templates with martinpetricko/laravel-database-mail. Activates when adding events that should send emails from DB templates; creating or editing mail templates; or when the user mentions database mail, email templates, event-triggered emails, or TriggersDatabaseMail.
- `developing-with-ai-sdk` — Builds AI agents, generates text and chat responses, produces images, synthesizes audio, transcribes speech, generates vector embeddings, reranks documents, and manages files and vector stores using the Laravel AI SDK (laravel/ai). Supports structured output, streaming, tools, conversation memory, middleware, queueing, broadcasting, and provider failover. Use when building, editing, updating, debugging, or testing any AI functionality, including agents, LLMs, chatbots, text generation, image generation, audio, transcription, embeddings, RAG, similarity search, vector stores, prompting, structured output, or any AI provider (OpenAI, Anthropic, Gemini, Cohere, Groq, xAI, ElevenLabs, Jina, OpenRouter).
- `developing-with-fortify` — Laravel Fortify headless authentication backend development. Activate when implementing authentication features including login, registration, password reset, email verification, two-factor authentication (2FA/TOTP), profile updates, headless auth, authentication scaffolding, or auth guards in Laravel applications.
- `documentation-automation` — Automates documentation when features are added or modified. Activates when creating Actions, Controllers, Pages, Routes, or Models; when modifying config/fortify.php; or when user mentions docs, documentation, readme.
- `durable-workflow` — Durable Workflow (laravel-workflow) and Waterline. Activates when defining workflows or activities, using WorkflowStub, monitoring workflows at /waterline, or when the user mentions durable workflow, Waterline, long-running workflows, sagas, or workflow orchestration.
- `laravel-data-table` — Server-side DataTables with machour/laravel-data-table (Laravel + Inertia + React, TanStack Table). Activates when building or editing data tables, DataTable classes, table columns/filters/sorting, quick views, exports, or when the user mentions DataTable, data table, server-side table, make:data-table.
- `laravel-excel` — Laravel Excel and Filament Excel exports (maatwebsite/excel, pxlrbt/filament-excel). Activates when adding or editing exports, imports, Filament table exports, DataTable exports, or when the user mentions Laravel Excel, Excel export, import, maatwebsite/excel, or filament-excel.
- `pan-product-analytics` — Product analytics with Pan (panphp/pan). Activates when adding or changing tabs, CTAs, nav links, buttons, or key UI that should be tracked for impressions, hovers, and clicks; or when the user mentions analytics, tracking, Pan, data-pan, or product analytics.
- `taylor-otwell-style` — Code PHP and Laravel applications in the style of Taylor Otwell — the creator of Laravel. Use this skill whenever the user asks to write PHP code, Laravel applications, packages,  APIs, services, or any backend code and wants it to follow Laravel conventions, Taylor  Otwell's coding philosophy, or "elegant PHP." Trigger on: Laravel development, PHP package  creation, API design, service classes, Eloquent models, migrations, controllers, middleware, artisan commands, service providers, fluent interfaces, collection pipelines, or any  request mentioning "Laravel-style," "expressive syntax," "Taylor Otwell," or "code like  Laravel." Also trigger when the user wants to refactor messy PHP into clean, idiomatic  Laravel code. Even if the user just says "write this in PHP" — if you can apply Laravel  patterns to make it better, consult this skill.
- `visibility-sharing` — Visibility and cross-organization sharing with HasVisibility. Activates when working with HasVisibility trait, VisibilityEnum, Shareable, VisibilityScope, shareItem policy, or copy-on-write cloning.

## Conventions

- You must follow all existing code conventions used in this application. When creating or editing a file, check sibling files for the correct structure, approach, and naming.
- Use descriptive names for variables and methods. For example, `isRegisteredForDiscounts`, not `discount()`.
- Check for existing components to reuse before writing a new one.

## Verification Scripts

- Do not create verification scripts or tinker when tests cover that functionality and prove they work. Unit and feature tests are more important.

## Application Structure & Architecture

- Stick to existing directory structure; don't create new base folders without approval.
- Do not change the application's dependencies without approval.

## Frontend Bundling

- If the user doesn't see a frontend change reflected in the UI, it could mean they need to run `npm run build`, `npm run dev`, or `composer run dev`. Ask them.

## Documentation Files

- You must only create documentation files if explicitly requested by the user.

## Replies

- Be concise in your explanations - focus on what's important rather than explaining obvious details.

=== boost rules ===

# Laravel Boost

## Tools

- Laravel Boost is an MCP server with tools designed specifically for this application. Prefer Boost tools over manual alternatives like shell commands or file reads.
- Use `database-query` to run read-only queries against the database instead of writing raw SQL in tinker.
- Use `database-schema` to inspect table structure before writing migrations or models.
- Use `get-absolute-url` to resolve the correct scheme, domain, and port for project URLs. Always use this before sharing a URL with the user.
- Use `browser-logs` to read browser logs, errors, and exceptions. Only recent logs are useful, ignore old entries.

## Searching Documentation (IMPORTANT)

- Always use `search-docs` before making code changes. Do not skip this step. It returns version-specific docs based on installed packages automatically.
- Pass a `packages` array to scope results when you know which packages are relevant.
- Use multiple broad, topic-based queries: `['rate limiting', 'routing rate limiting', 'routing']`. Expect the most relevant results first.
- Do not add package names to queries because package info is already shared. Use `test resource table`, not `filament 4 test resource table`.

### Search Syntax

1. Use words for auto-stemmed AND logic: `rate limit` matches both "rate" AND "limit".
2. Use `"quoted phrases"` for exact position matching: `"infinite scroll"` requires adjacent words in order.
3. Combine words and phrases for mixed queries: `middleware "rate limit"`.
4. Use multiple queries for OR logic: `queries=["authentication", "middleware"]`.

## Artisan

- Run Artisan commands directly via the command line (e.g., `php artisan route:list`). Use `php artisan list` to discover available commands and `php artisan [command] --help` to check parameters.
- Inspect routes with `php artisan route:list`. Filter with: `--method=GET`, `--name=users`, `--path=api`, `--except-vendor`, `--only-vendor`.
- Read configuration values using dot notation: `php artisan config:show app.name`, `php artisan config:show database.default`. Or read config files directly from the `config/` directory.
- To check environment variables, read the `.env` file directly.

## Tinker

- Execute PHP in app context for debugging and testing code. Do not create models without user approval, prefer tests with factories instead. Prefer existing Artisan commands over custom tinker code.
- Always use single quotes to prevent shell expansion: `php artisan tinker --execute 'Your::code();'`
  - Double quotes for PHP strings inside: `php artisan tinker --execute 'User::where("active", true)->count();'`

=== php rules ===

# PHP

- Always declare `declare(strict_types=1);` at the top of every `.php` file.
- Always use curly braces for control structures, even for single-line bodies.
- Use PHP 8 constructor property promotion: `public function __construct(public GitHub $github) { }`. Do not leave empty zero-parameter `__construct()` methods unless the constructor is private.
- Use explicit return type declarations and type hints for all method parameters: `function isAccessible(User $user, ?string $path = null): bool`
- Use TitleCase for Enum keys: `FavoritePerson`, `BestLake`, `Monthly`.
- Prefer PHPDoc blocks over inline comments. Only add inline comments for exceptionally complex logic.
- Use array shape type definitions in PHPDoc blocks.

=== inertia-laravel/core rules ===

# Inertia

- Inertia creates fully client-side rendered SPAs without modern SPA complexity, leveraging existing server-side patterns.
- Components live in `resources/js/pages` (unless specified in `vite.config.js`). Use `Inertia::render()` for server-side routing instead of Blade views.
- ALWAYS use `search-docs` tool for version-specific Inertia documentation and updated code examples.
- IMPORTANT: Activate `inertia-react-development` when working with Inertia client-side patterns.

# Inertia v3

- Use all Inertia features from v1, v2, and v3. Check the documentation before making changes to ensure the correct approach.
- New v3 features: standalone HTTP requests (`useHttp` hook), optimistic updates with automatic rollback, layout props (`useLayoutProps` hook), instant visits, simplified SSR via `@inertiajs/vite` plugin, custom exception handling for error pages.
- Carried over from v2: deferred props, infinite scroll, merging props, polling, prefetching, once props, flash data.
- When using deferred props, add an empty state with a pulsing or animated skeleton.
- Axios has been removed. Use the built-in XHR client with interceptors, or install Axios separately if needed.
- `Inertia::lazy()` / `LazyProp` has been removed. Use `Inertia::optional()` instead.
- Prop types (`Inertia::optional()`, `Inertia::defer()`, `Inertia::merge()`) work inside nested arrays with dot-notation paths.
- SSR works automatically in Vite dev mode with `@inertiajs/vite` - no separate Node.js server needed during development.
- Event renames: `invalid` is now `httpException`, `exception` is now `networkError`.
- `router.cancel()` replaced by `router.cancelAll()`.
- The `future` configuration namespace has been removed - all v2 future options are now always enabled.

=== ai/core rules ===

## Laravel AI SDK

- This application uses the Laravel AI SDK (`laravel/ai`) for all AI functionality.
- Activate the `developing-with-ai-sdk` skill when building, editing, updating, debugging, or testing AI agents, text generation, chat, streaming, structured output, tools, image generation, audio, transcription, embeddings, reranking, vector stores, files, conversation memory, or any AI provider integration (OpenAI, Anthropic, Gemini, Cohere, Groq, xAI, ElevenLabs, Jina, OpenRouter).

=== laravel/core rules ===

# Do Things the Laravel Way

- Use `php artisan make:` commands to create new files (i.e. migrations, controllers, models, etc.). You can list available Artisan commands using `php artisan list` and check their parameters with `php artisan [command] --help`.
- If you're creating a generic PHP class, use `php artisan make:class`.
- Pass `--no-interaction` to all Artisan commands to ensure they work without user input. You should also pass the correct `--options` to ensure correct behavior.

### Model Creation

- When creating new models, create useful factories and seeders for them too. Ask the user if they need any other things, using `php artisan make:model --help` to check the available options.

## APIs & Eloquent Resources

- For APIs, default to using Eloquent API Resources and API versioning unless existing API routes do not, then you should follow existing application convention.

## URL Generation

- When generating links to other pages, prefer named routes and the `route()` function.

## Testing

- When creating models for tests, use the factories for the models. Check if the factory has custom states that can be used before manually setting up the model.
- Faker: Use methods such as `$this->faker->word()` or `fake()->randomDigit()`. Follow existing conventions whether to use `$this->faker` or `fake()`.
- When creating tests, make use of `php artisan make:test [options] {name}` to create a feature test, and pass `--unit` to create a unit test. Most tests should be feature tests.

## Vite Error

- If you receive an "Illuminate\Foundation\ViteException: Unable to locate file in Vite manifest" error, you can run `npm run build` or ask the user to run `npm run dev` or `composer run dev`.

## Deployment

- Laravel can be deployed using [Laravel Cloud](https://cloud.laravel.com/), which is the fastest way to deploy and scale production Laravel applications.

=== wayfinder/core rules ===

# Laravel Wayfinder

Use Wayfinder to generate TypeScript functions for Laravel routes. Import from `@/actions/` (controllers) or `@/routes/` (named routes).

=== pint/core rules ===

# Laravel Pint Code Formatter

- If you have modified any PHP files, you must run `vendor/bin/pint --dirty --format agent` before finalizing changes to ensure your code matches the project's expected style.
- Do not run `vendor/bin/pint --test --format agent`, simply run `vendor/bin/pint --format agent` to fix any formatting issues.

=== pest/core rules ===

## Pest

- This project uses Pest for testing. Create tests: `php artisan make:test --pest {name}`.
- Run tests: `php artisan test --compact` or filter: `php artisan test --compact --filter=testName`.
- Do NOT delete tests without approval.

=== inertia-react/core rules ===

# Inertia + React

- IMPORTANT: Activate `inertia-react-development` when working with Inertia React client-side patterns.

=== filament/filament rules ===

## Filament

- Filament is used by this application. Follow the existing conventions for how and where it is implemented.
- Filament is a Server-Driven UI (SDUI) framework for Laravel that lets you define user interfaces in PHP using structured configuration objects. Built on Livewire, Alpine.js, and Tailwind CSS.
- Use the `search-docs` tool for official documentation on Artisan commands, code examples, testing, relationships, and idiomatic practices. If `search-docs` is unavailable, refer to https://filamentphp.com/docs.

### Artisan

- Always use Filament-specific Artisan commands to create files. Find available commands with the `list-artisan-commands` tool, or run `php artisan --help`.
- Always inspect required options before running a command, and always pass `--no-interaction`.

### Patterns

Always use static `make()` methods to initialize components. Most configuration methods accept a `Closure` for dynamic values.

Use `Get $get` to read other form field values for conditional logic:

<code-snippet name="Conditional form field visibility" lang="php">
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Utilities\Get;

Select::make('type')
    ->options(CompanyType::class)
    ->required()
    ->live(),

TextInput::make('company_name')
    ->required()
    ->visible(fn (Get $get): bool => $get('type') === 'business'),

</code-snippet>

Use `state()` with a `Closure` to compute derived column values:

<code-snippet name="Computed table column value" lang="php">
use Filament\Tables\Columns\TextColumn;

TextColumn::make('full_name')
    ->state(fn (User $record): string => "{$record->first_name} {$record->last_name}"),

</code-snippet>

Actions encapsulate a button with an optional modal form and logic:

<code-snippet name="Action with modal form" lang="php">
use Filament\Actions\Action;
use Filament\Forms\Components\TextInput;

Action::make('updateEmail')
    ->schema([
        TextInput::make('email')
            ->email()
            ->required(),
    ])
    ->action(fn (array $data, User $record) => $record->update($data))

</code-snippet>

### Testing

Always authenticate before testing panel functionality. Filament uses Livewire, so use `Livewire::test()` or `livewire()` (available when `pestphp/pest-plugin-livewire` is in `composer.json`):

<code-snippet name="Table test" lang="php">
use function Pest\Livewire\livewire;

livewire(ListUsers::class)
    ->assertCanSeeTableRecords($users)
    ->searchTable($users->first()->name)
    ->assertCanSeeTableRecords($users->take(1))
    ->assertCanNotSeeTableRecords($users->skip(1));

</code-snippet>

<code-snippet name="Create resource test" lang="php">
use function Pest\Laravel\assertDatabaseHas;
use function Pest\Livewire\livewire;

livewire(CreateUser::class)
    ->fillForm([
        'name' => 'Test',
        'email' => 'test@example.com',
    ])
    ->call('create')
    ->assertNotified()
    ->assertRedirect();

assertDatabaseHas(User::class, [
    'name' => 'Test',
    'email' => 'test@example.com',
]);

</code-snippet>

<code-snippet name="Testing validation" lang="php">
use function Pest\Livewire\livewire;

livewire(CreateUser::class)
    ->fillForm([
        'name' => null,
        'email' => 'invalid-email',
    ])
    ->call('create')
    ->assertHasFormErrors([
        'name' => 'required',
        'email' => 'email',
    ])
    ->assertNotNotified();

</code-snippet>

<code-snippet name="Calling actions in pages" lang="php">
use Filament\Actions\DeleteAction;
use function Pest\Livewire\livewire;

livewire(EditUser::class, ['record' => $user->id])
    ->callAction(DeleteAction::class)
    ->assertNotified()
    ->assertRedirect();

</code-snippet>

<code-snippet name="Calling actions in tables" lang="php">
use Filament\Actions\Testing\TestAction;
use function Pest\Livewire\livewire;

livewire(ListUsers::class)
    ->callAction(TestAction::make('promote')->table($user), [
        'role' => 'admin',
    ])
    ->assertNotified();

</code-snippet>

### Correct Namespaces

- Form fields (`TextInput`, `Select`, etc.): `Filament\Forms\Components\`
- Infolist entries (`TextEntry`, `IconEntry`, etc.): `Filament\Infolists\Components\`
- Layout components (`Grid`, `Section`, `Fieldset`, `Tabs`, `Wizard`, etc.): `Filament\Schemas\Components\`
- Schema utilities (`Get`, `Set`, etc.): `Filament\Schemas\Components\Utilities\`
- Actions (`DeleteAction`, `CreateAction`, etc.): `Filament\Actions\`. Never use `Filament\Tables\Actions\`, `Filament\Forms\Actions\`, or any other sub-namespace for actions.
- Icons: `Filament\Support\Icons\Heroicon` enum (e.g., `Heroicon::PencilSquare`)

### Common Mistakes

- **Never assume public file visibility.** File visibility is `private` by default. Always use `->visibility('public')` when public access is needed.
- **Never assume full-width layout.** `Grid`, `Section`, and `Fieldset` do not span all columns by default. Explicitly set column spans when needed.

=== prism-php/prism rules ===

## Prism

- Prism is a Laravel package for integrating Large Language Models (LLMs) into applications with a fluent, expressive and eloquent API.
- IMPORTANT: Activate `developing-with-prism` skill when working with Prism features.

=== filament/blueprint rules ===

## Filament Blueprint

You are writing Filament v5 implementation plans. Plans must be specific enough
that an implementing agent can write code without making decisions.

**Start here**: Read
`/vendor/filament/blueprint/resources/markdown/planning/overview.md` for plan format,
required sections, and what to clarify with the user before planning.

</laravel-boost-guidelines>

---

## Project conventions (survives boost:update)

These apply in addition to the Laravel Boost guidelines above. They are kept **after** the `</laravel-boost-guidelines>` block so `php artisan boost:update` does not remove them.

- **Multi-tenancy:** Organizations own content; use `TenantContext`, `SetTenantContext`, `EnsureTenantContext` (`tenant` middleware), and `BelongsToOrganization` trait. Spatie permissions use `organization_id` as team. Domain/subdomain resolution via `ResolveDomainMiddleware` and `organization_domains`. Single-tenant mode: `MULTI_ORGANIZATION_ENABLED=false` hides org UI. See `config/tenancy.php`, docs/developer/backend/billing-and-tenancy.md, docs/developer/backend/single-tenant-mode.md.
- **Org permissions:** JSON-driven org permissions in `database/seeders/data/organization-permissions.json`; run `permission:sync` to create and assign. Use `$user->canInOrganization()`, `@canOrg`, etc. See docs/developer/backend/permissions.md.
- **Billing:** laravelcm/laravel-subscriptions + Stripe + Lemon Squeezy (one-time products); `HasCredits` and `HasBilling` traits on Organization; seat-based billing (`BillingSettings`, `SyncSubscriptionSeatsAction`); billing routes under `tenant` middleware. See `config/billing.php`, `app/Http/Controllers/Billing/`, docs/developer/backend/billing-and-tenancy.md, docs/developer/backend/lemon-squeezy.md.
- **Full-text search:** Use Laravel Scout; driver Typesense (Herd: `SCOUT_DRIVER=typesense`, `TYPESENSE_API_KEY=LARAVEL-HERD`, `TYPESENSE_HOST=localhost`). Add `Searchable` trait and `toSearchableArray()` (id as string, created_at as UNIX timestamp); define collection schema in `config/scout.php` under `typesense.model-settings`. See docs/developer/backend/scout-typesense.md.
- **Third-party APIs:** use Saloon; add connectors and requests under `App\Http\Integrations\{Name}\` (see docs/developer/backend/saloon.md).
- **Server-side DataTables:** machour/laravel-data-table (installed from fork coding-sunshine/laravel-data-table via VCS). One PHP class per model in `App\DataTables\*` (DTO + table config); Inertia + React UI; run `npx shadcn@latest add ./vendor/machour/laravel-data-table/react/public/r/data-table.json` to install React components. To develop the package in place, use a Composer path repository. See docs/developer/backend/data-table.md.
- **Backups:** spatie/laravel-backup (v10) (config/backup.php, docs/developer/backend/backup.md).
- **Userstamps:** wildside/userstamps for created_by/updated_by (docs/developer/backend/userstamps.md).
- **Visibility & Sharing:** For global/org/shared data and cross-org sharing use `HasVisibility` trait (not with `BelongsToOrganization` on same model). Requires `organization_id`, `visibility`; optional `cloned_from`. Share via `Shareable`; authorize with `shareItem`. See docs/developer/backend/visibility-sharing.md.
- **Product analytics (Pan):** panphp/pan tracks impressions, hovers, and clicks via `data-pan="name"` on HTML elements. Use only letters, numbers, dashes, underscores. Add new names to `AppServiceProvider::configurePan()` allowedAnalytics whitelist. View with `php artisan pan` or in Filament at Analytics → Product Analytics (`/admin/analytics/product`). See docs/developer/backend/pan.md. When adding new tabs, CTAs, or key nav/buttons, add `data-pan` and register the name in the whitelist.
- **Database Mail (email templates):** martinpetricko/laravel-database-mail stores email templates in the DB and sends them when events are dispatched. For new events that should send DB-backed emails: implement `TriggersDatabaseMail` and `CanTriggerDatabaseMail`, define `getName()`, `getDescription()`, `getRecipients()`, and optionally `getAttachments()`; register the event in `config/database-mail.php` under `'events'`. Create templates via seeders or Filament plugin. See docs/developer/backend/database-mail.md.
- **Architecture decisions:** record in docs/architecture/ADRs/ (see README there).
- **Durable Workflow & Waterline:** laravel-workflow/laravel-workflow for long-running workflows (sagas, onboarding, AI pipelines); laravel-workflow/waterline UI at `/waterline` (admin only). Workflows run on Laravel queues (Horizon). Gate `viewWaterline` same as Horizon (`access admin panel`). See docs/developer/backend/durable-workflow.md.
- **Health checks:** spatie/laravel-health for scheduled checks and notifications (mail by default in `config/health.php`; Slack can be enabled once notifications are compatible with your Slack channel stack); checks registered in HealthServiceProvider; `health:check` scheduled every 5 minutes. See docs/developer/backend/health.md.
