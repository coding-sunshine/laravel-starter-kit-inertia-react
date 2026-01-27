# Documentation Guidelines

## Automatic Documentation Triggers

When completing work involving these paths, documentation updates are REQUIRED:

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

## Self-Check Before Completing Tasks

Before marking any task complete, verify:

- [ ] Did I create/modify an Action? → Use `application-info`, document in `docs/developer/backend/actions/`
- [ ] Did I add a route? → Use `list-routes`, update `docs/developer/api-reference/routes.md`
- [ ] Did I change the database? → Use `database-schema`, update model docs
- [ ] Is this user-visible? → Update `docs/user-guide/`
- [ ] Did I update the manifest? → Update `docs/.manifest.json`

## Documentation Generation Process

@boostsnippet('Documentation workflow', 'text')
1. Detect change type (Action, Controller, Page, Route)
2. Use Boost tools to gather context:
   - application-info → Models, packages
   - database-schema → Related tables (if data-related)
   - list-routes → Affected routes
   - search-docs → Laravel best practices (if applicable)
3. Determine documentation scope using decision matrix
4. Generate documentation using appropriate template
5. Update manifest at docs/.manifest.json
6. Update relevant index/README files
@endboostsnippet
