# Command Palette

Global Cmd+K (Ctrl+K) palette. Mounted once in `resources/js/layouts/app/app-sidebar-layout.tsx`.

## Files

| Path | Role |
|---|---|
| `resources/js/components/command-palette/command-palette.tsx` | UI |
| `resources/js/components/command-palette/use-command-shortcut.ts` | Keyboard binding |
| `resources/js/components/command-palette/static-actions.ts` | Hard-coded jump targets |
| `resources/js/components/command-palette/types.ts` | Shared TS types |
| `resources/js/stores/command-palette-store.ts` | Zustand open/close state |
| `app/Http/Controllers/Search/CommandPaletteSearchController.php` | `/api/command-palette/search` |
| `app/Actions/Search/SearchForCommandPaletteAction.php` | DB-backed search |
| `app/Actions/Search/CommandPaletteResults.php` | Result DTO |

## Adding a static action

Append to `STATIC_ACTIONS` in `static-actions.ts`. Use a Wayfinder route helper for `href` once the relevant route surface ships.

## Adding a new searchable category

1. Extend `CommandPaletteResults` with a new array property.
2. Add a `WHERE LIKE`/`ILIKE` block to `SearchForCommandPaletteAction::handle()`.
3. Render a new `<CommandGroup>` in `command-palette.tsx`.
4. Add a test case to `SearchForCommandPaletteActionTest`.

## Performance

200ms debounce, ≤10 hits per category. PostgreSQL `ILIKE` (or SQLite `LIKE` in tests) over indexed columns. If categories grow past ~100k rows, switch to Scout / Typesense — only the Action changes.
