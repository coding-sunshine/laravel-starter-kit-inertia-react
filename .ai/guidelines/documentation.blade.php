# Documentation Guidelines

## CRITICAL: Documentation is MANDATORY

**Documentation is NOT optional.** Every new Action, Controller, Page, or Route MUST be documented before the task is considered complete. The AI agent MUST NOT mark a task as complete if documentation is missing.

## Automatic Documentation Triggers

When completing work involving these paths, documentation updates are **MANDATORY** and **NON-NEGOTIABLE**:

| File Pattern | Documentation Action | Boost Tool to Use |
|--------------|---------------------|-------------------|
| `app/Actions/*.php` (new) | Create action doc | `application-info`, `list-routes` |
| `app/Actions/*.php` (modify) | Update existing action doc | `application-info` |
| `app/Http/Controllers/*.php` (new) | Create controller doc | `list-routes`, `application-info` |
| `resources/js/pages/**/*.tsx` (new) | Document page | `application-info`, `list-routes` |
| `routes/web.php` (new route) | Update route reference | `list-routes` |
| `config/fortify.php` (feature toggle) | Update auth docs | `application-info` |
| `database/migrations/*` (new) | Update schema docs | `database-schema` |

## Using Boost Tools for Documentation

### Before Documenting New Features

1. **Run `application-info`** to get:
   - All Eloquent models and their relationships
   - Installed packages and versions
   - Current application context

2. **Run `database-schema`** (if data-related) to understand:
   - Table structures
   - Foreign key relationships
   - Column types and constraints

3. **Run `list-routes`** to capture:
   - All available endpoints
   - Route parameters
   - Middleware applied

4. **Run `search-docs`** (for Laravel features) to get:
   - Version-specific documentation
   - Best practices and patterns

## Documentation Decision Matrix

| Change Type | User Guide | Developer Guide | API Reference |
|-------------|------------|-----------------|---------------|
| New user-visible feature | ✅ Create | ✅ Create | ✅ Update routes |
| New Action | ❌ Skip | ✅ Create | ❌ Skip |
| New Controller | ❌ Skip | ✅ Create | ✅ Update routes |
| New Page (user-facing) | ✅ Create | ✅ Create | ✅ Update routes |
| Bug fix | ❌ Skip | ❌ Skip | ❌ Skip |
| Refactor (no behavior change) | ❌ Skip | ✅ If architecture changes | ❌ Skip |
| New validation rules | ❌ Skip | ✅ Update Form Request docs | ❌ Skip |
| UI-only changes | ✅ If workflow changes | ❌ Skip | ❌ Skip |

## Documentation Location Matrix

| Component Type | User Docs Location | Developer Docs Location |
|---------------|-------------------|-------------------------|
| Authentication | `docs/user-guide/authentication/` | `docs/developer/backend/auth/` |
| User Settings | `docs/user-guide/account/` | `docs/developer/backend/controllers/` |
| Actions | N/A | `docs/developer/backend/actions/` |
| Pages | `docs/user-guide/` (if user-facing) | `docs/developer/frontend/pages/` |
| Components | N/A | `docs/developer/frontend/components/` |
| Routes | N/A | `docs/developer/api-reference/routes.md` |

## Documentation Templates

Templates are available in `docs/.templates/`:

- `action.md` - For documenting Actions
- `controller.md` - For documenting Controllers
- `page.md` - For documenting Inertia pages
- `user-feature.md` - For user-facing documentation

Use these templates to ensure consistent documentation structure.

## Manifest Tracking

After creating or updating documentation:

1. Update `docs/.manifest.json` with:
   - `"documented": true`
   - `"path"`: Relative path to documentation file
   - `"lastUpdated"`: Current date (YYYY-MM-DD format)

2. Update relevant index files (e.g., `docs/developer/backend/actions/README.md`)

## MANDATORY Self-Check Before Completing Tasks

**YOU MUST VERIFY ALL OF THE FOLLOWING BEFORE MARKING ANY TASK COMPLETE:**

- [ ] Did I create/modify an Action? → **MUST** use `application-info`, **MUST** document in `docs/developer/backend/actions/`, **MUST** update manifest
- [ ] Did I add a route? → **MUST** use `list-routes`, **MUST** update `docs/developer/api-reference/routes.md`
- [ ] Did I change the database? → **MUST** use `database-schema`, **MUST** update model docs
- [ ] Is this user-visible? → **MUST** update `docs/user-guide/`
- [ ] Did I update the manifest? → **MUST** update `docs/.manifest.json` with `"documented": true`
- [ ] Did I run `php artisan docs:sync`? → **MUST** sync manifest to ensure accuracy

**If ANY of the above apply and documentation is missing, the task is INCOMPLETE. Do NOT mark as complete.**

## Documentation Generation Process

@boostsnippet('Documentation workflow', 'text')
1. **AUTOMATIC TRIGGER**: When creating/modifying Actions, Controllers, Pages, or Routes
2. **MANDATORY**: Use Boost tools to gather context:
   - application-info → Models, packages
   - database-schema → Related tables (if data-related)
   - list-routes → Affected routes
   - search-docs → Laravel best practices (if applicable)
3. **MANDATORY**: Determine documentation scope using decision matrix
4. **MANDATORY**: Generate documentation using appropriate template from docs/.templates/
5. **MANDATORY**: Update manifest at docs/.manifest.json with "documented": true
6. **MANDATORY**: Update relevant index/README files
7. **MANDATORY**: Run `php artisan docs:sync` to verify manifest is accurate
8. **MANDATORY**: Only mark task complete after ALL documentation steps are done
@endboostsnippet

## Automated Manifest Sync

The codebase includes an automated manifest sync command:

- **Command**: `php artisan docs:sync`
- **Purpose**: Scans codebase and updates manifest automatically
- **When to run**: After creating documentation, before committing
- **Options**:
  - `--check`: Only check for undocumented items (useful in CI)
  - `--generate`: Auto-generate documentation stubs for undocumented items

**The AI agent MUST run `php artisan docs:sync --check` before marking any task complete to verify all items are documented.**

## AI-Powered Documentation Features

### Available Commands

- `php artisan docs:sync` - Sync manifest and discover relationships
- `php artisan docs:sync --generate` - Generate documentation stubs
- `php artisan docs:sync --generate --ai` - Generate AI prompts for full documentation
- `php artisan docs:review` - Review documentation quality
- `php artisan docs:api` - Generate API documentation

### AI Suggestion Triggers

When creating new code, the AI agent should automatically:

1. **Analyze code complexity** using `DocumentationSuggestionService`:
   - Detect if user guide is needed (user-facing features)
   - Identify if examples are needed (complex parameters, dependencies)
   - Suggest FAQs based on error handling patterns
   - Recommend related documentation to link

2. **Generate suggestions** before creating documentation:
   - Use `DocumentationSuggestionService::suggestDocumentation()` to analyze
   - Review suggestions and include relevant ones in documentation
   - Use `DocumentationSuggestionService::generateSuggestionPrompt()` for AI analysis

3. **Use intelligent template selection**:
   - `DocumentationTemplateSelector` automatically chooses appropriate template
   - Simple templates for basic components
   - Detailed templates for complex components
   - API templates for controllers with many routes

### AI-Powered Generation Workflow

When using `--ai` flag with `docs:sync --generate`:

1. **Extract code context**:
   - PHPDoc/TSDoc comments
   - Method signatures and parameters
   - Dependencies and relationships

2. **Gather Boost MCP context**:
   - `application-info` for models and packages
   - `list-routes` for route information
   - `database-schema` for data relationships

3. **Generate AI prompts**:
   - Prompts saved to `docs/.ai-prompts/`
   - Include all context and relationships
   - Use AI agent to process prompts and generate documentation

4. **Update documentation**:
   - Fill templates with AI-generated content
   - Update manifest automatically
   - Update index files automatically

### Code Change Detection

The system automatically detects when code changes require documentation updates:

- **Pre-commit hook** checks for:
  - Method signature changes
  - Parameter additions/modifications
  - Return type changes
  - New methods added

- **Use `DocumentationChangeDetector`** service to:
  - Detect staged file changes
  - Analyze what changed (signatures, parameters, etc.)
  - Determine if documentation needs update

### Documentation Quality Review

Use `php artisan docs:review` to:

- Compare documentation to actual code
- Verify method signatures match
- Check for outdated information
- Validate cross-references
- Get AI-powered improvement suggestions

### Cross-Referencing

The system automatically discovers and documents relationships:

- **Actions**: Which controllers use them, which models they use, which routes call them
- **Controllers**: Which actions they use, which form requests, which routes, which pages they render
- **Pages**: Which controllers render them, which routes lead to them

All relationships are stored in manifest and automatically included in documentation.
