# Command Palette (Cmd+K) — Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task.

**Goal:** Add a global Cmd+K (Ctrl+K) palette letting any user jump to a rake by number / FNR / RR or RR number, jump to common pages, and trigger a small set of actions. Works from any page.

**Architecture:** Single React component `<CommandPalette>` mounted once in the existing app layout. State held in a Zustand store so any keyboard listener can open/close without prop drilling. Search delegates to a thin Inertia controller `CommandPaletteSearchController` returning a JSON envelope (rake / indent / RR hits) backed by indexed columns. Direct PostgreSQL `ILIKE` (no Scout dependency) — fast enough at < 50k rakes. Jumps use Wayfinder routes; actions are static for v1. 200ms debounce. Keyboard-first via existing `cmdk`-based `CommandDialog` primitive.

**Tech Stack:** React 19, Inertia v3, TypeScript, Tailwind CSS v4, `cmdk` (already installed), Zustand (already installed), Lucide React, Pest 4, Vitest, Laravel 13

**Depends on:** none. Pure additive.

---

## File Structure

**Created:**
- `resources/js/stores/command-palette-store.ts`
- `resources/js/components/command-palette/command-palette.tsx`
- `resources/js/components/command-palette/use-command-shortcut.ts`
- `resources/js/components/command-palette/types.ts`
- `resources/js/components/command-palette/static-actions.ts`
- `resources/js/components/command-palette/command-palette.test.tsx`
- `app/Http/Controllers/Search/CommandPaletteSearchController.php`
- `app/Actions/Search/SearchForCommandPaletteAction.php`
- `app/Actions/Search/CommandPaletteResults.php`
- `tests/Feature/Search/CommandPaletteSearchControllerTest.php`
- `tests/Unit/Actions/Search/SearchForCommandPaletteActionTest.php`
- `docs/developer/frontend/components/command-palette.md`
- `docs/user-guide/keyboard-shortcuts.md`

**Modified:**
- `resources/js/layouts/app/app-sidebar-layout.tsx` (or whichever shell renders globally — discovered in Task 8)
- `routes/web.php`
- `docs/.manifest.json`

---

### Task 1: Backend DTO

**File:** `app/Actions/Search/CommandPaletteResults.php`

```php
<?php

declare(strict_types=1);

namespace App\Actions\Search;

/**
 * @phpstan-type RakeHit array{id: int, rake_number: string, siding_name: string|null, status: string|null}
 * @phpstan-type IndentHit array{id: int, indent_number: string, e_demand_number: string|null}
 * @phpstan-type RrHit array{id: int, rr_number: string, rake_id: int|null}
 */
final readonly class CommandPaletteResults
{
    /**
     * @param  list<RakeHit>  $rakes
     * @param  list<IndentHit>  $indents
     * @param  list<RrHit>  $rrs
     */
    public function __construct(
        public array $rakes = [],
        public array $indents = [],
        public array $rrs = [],
    ) {}
}
```

Commit: `feat(search): add CommandPaletteResults DTO`

---

### Task 2: `SearchForCommandPaletteAction`

**Step 1 — Confirm columns exist:**

```bash
php artisan tinker --execute 'echo implode(",", \Illuminate\Support\Facades\Schema::getColumnListing("rakes"));'
php artisan tinker --execute 'echo implode(",", \Illuminate\Support\Facades\Schema::getColumnListing("indents"));'
```

If `indent_number` or `e_demand_number` don't exist on `indents`, substitute the actual column names below (search the migration history).

For RR: confirm whether the table is `railway_receipts`, `rr_documents`, or other:

```bash
php artisan tinker --execute 'foreach(["railway_receipts","rr_documents","rrs"] as $t) { echo $t.":".(\Illuminate\Support\Facades\Schema::hasTable($t)?"yes":"no").PHP_EOL; }'
```

Use whichever returns "yes".

**Step 2 — Failing test:**

`tests/Unit/Actions/Search/SearchForCommandPaletteActionTest.php`:

```php
<?php

declare(strict_types=1);

use App\Actions\Search\SearchForCommandPaletteAction;
use App\Models\Indent;
use App\Models\Rake;
use App\Models\Siding;

beforeEach(function (): void {
    $this->action = app(SearchForCommandPaletteAction::class);
});

it('returns empty results for short queries', function (): void {
    $results = $this->action->handle('a');
    expect($results->rakes)->toBe([])
        ->and($results->indents)->toBe([])
        ->and($results->rrs)->toBe([]);
});

it('finds rakes by partial rake_number', function (): void {
    $siding = Siding::factory()->create(['name' => 'Dumka']);
    Rake::factory()->create(['siding_id' => $siding->id, 'rake_number' => 'DUMK-12345']);
    Rake::factory()->create(['siding_id' => $siding->id, 'rake_number' => 'DUMK-67890']);

    $results = $this->action->handle('123');
    expect($results->rakes)->toHaveCount(1)
        ->and($results->rakes[0]['rake_number'])->toBe('DUMK-12345');
});

it('caps results at 10 per category', function (): void {
    $siding = Siding::factory()->create();
    Rake::factory()->count(15)->create([
        'siding_id' => $siding->id,
        'rake_number' => fn () => 'RAK-'.fake()->unique()->numberBetween(10000, 99999),
    ]);

    $results = $this->action->handle('RAK');
    expect($results->rakes)->toHaveCount(10);
});

it('searches indents by indent_number and e_demand_number', function (): void {
    Indent::factory()->create(['indent_number' => 'IND-7777', 'e_demand_number' => 'ED-1234']);

    $byIndent = $this->action->handle('7777');
    $byEDemand = $this->action->handle('ED-12');

    expect($byIndent->indents)->toHaveCount(1)
        ->and($byEDemand->indents)->toHaveCount(1);
});
```

Run: `php artisan test --filter=SearchForCommandPaletteActionTest --compact`. Expect FAIL.

**Step 3 — Action:**

```php
<?php

declare(strict_types=1);

namespace App\Actions\Search;

use App\Models\Indent;
use App\Models\Rake;
use Illuminate\Support\Facades\DB;

final class SearchForCommandPaletteAction
{
    private const MIN_QUERY_LENGTH = 2;

    private const PER_CATEGORY_LIMIT = 10;

    public function handle(string $query): CommandPaletteResults
    {
        $q = trim($query);

        if (mb_strlen($q) < self::MIN_QUERY_LENGTH) {
            return new CommandPaletteResults();
        }

        $like = '%'.str_replace(['%', '_'], ['\%', '\_'], $q).'%';

        $rakes = Rake::query()
            ->select(['rakes.id', 'rakes.rake_number', 'sidings.name as siding_name', 'rakes.status'])
            ->leftJoin('sidings', 'sidings.id', '=', 'rakes.siding_id')
            ->where('rakes.rake_number', 'ILIKE', $like)
            ->orderByDesc('rakes.id')
            ->limit(self::PER_CATEGORY_LIMIT)
            ->get()
            ->map(fn ($r): array => [
                'id' => (int) $r->id,
                'rake_number' => (string) $r->rake_number,
                'siding_name' => $r->siding_name,
                'status' => $r->status,
            ])
            ->values()
            ->all();

        $indents = Indent::query()
            ->select(['id', 'indent_number', 'e_demand_number'])
            ->where(function ($builder) use ($like): void {
                $builder->where('indent_number', 'ILIKE', $like)
                    ->orWhere('e_demand_number', 'ILIKE', $like);
            })
            ->orderByDesc('id')
            ->limit(self::PER_CATEGORY_LIMIT)
            ->get()
            ->map(fn ($i): array => [
                'id' => (int) $i->id,
                'indent_number' => (string) $i->indent_number,
                'e_demand_number' => $i->e_demand_number,
            ])
            ->values()
            ->all();

        $rrTable = DB::getSchemaBuilder()->hasTable('railway_receipts') ? 'railway_receipts' : 'rr_documents';
        $rrs = DB::table($rrTable)
            ->select(['id', 'rr_number', 'rake_id'])
            ->where('rr_number', 'ILIKE', $like)
            ->orderByDesc('id')
            ->limit(self::PER_CATEGORY_LIMIT)
            ->get()
            ->map(fn ($r): array => [
                'id' => (int) $r->id,
                'rr_number' => (string) $r->rr_number,
                'rake_id' => isset($r->rake_id) ? (int) $r->rake_id : null,
            ])
            ->values()
            ->all();

        return new CommandPaletteResults(rakes: $rakes, indents: $indents, rrs: $rrs);
    }
}
```

Re-run → expect PASS, 4 tests. If `Status` column on `rakes` is named differently (e.g. `state`), adjust.

If `Indent::factory()` doesn't accept `indent_number` / `e_demand_number` directly, inspect `database/factories/IndentFactory.php` and adjust the test setup accordingly. Don't relax assertions.

Commit: `feat(search): SearchForCommandPaletteAction`

---

### Task 3: Controller + route

**Step 1 — Failing test:**

`tests/Feature/Search/CommandPaletteSearchControllerTest.php`:

```php
<?php

declare(strict_types=1);

use App\Models\Rake;
use App\Models\Siding;
use App\Models\User;

it('rejects unauthenticated requests', function (): void {
    $this->getJson('/api/command-palette/search?q=DUMK')
        ->assertStatus(401);
});

it('returns the expected JSON envelope', function (): void {
    $user = User::factory()->create();
    $siding = Siding::factory()->create();
    Rake::factory()->create(['siding_id' => $siding->id, 'rake_number' => 'DUMK-1234']);

    $this->actingAs($user)
        ->getJson('/api/command-palette/search?q=DUMK')
        ->assertOk()
        ->assertJsonStructure([
            'rakes' => [['id', 'rake_number', 'siding_name', 'status']],
            'indents',
            'rrs',
        ])
        ->assertJsonPath('rakes.0.rake_number', 'DUMK-1234');
});

it('returns empty arrays when query is too short', function (): void {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->getJson('/api/command-palette/search?q=a')
        ->assertOk()
        ->assertJson(['rakes' => [], 'indents' => [], 'rrs' => []]);
});
```

**Step 2 — Controller:**

`app/Http/Controllers/Search/CommandPaletteSearchController.php`:

```php
<?php

declare(strict_types=1);

namespace App\Http\Controllers\Search;

use App\Actions\Search\SearchForCommandPaletteAction;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final readonly class CommandPaletteSearchController
{
    public function __invoke(Request $request, SearchForCommandPaletteAction $action): JsonResponse
    {
        $validated = $request->validate([
            'q' => ['nullable', 'string', 'max:128'],
        ]);

        $results = $action->handle((string) ($validated['q'] ?? ''));

        return response()->json([
            'rakes' => $results->rakes,
            'indents' => $results->indents,
            'rrs' => $results->rrs,
        ]);
    }
}
```

**Step 3 — Route in `routes/web.php`** (inside the existing authenticated group — match convention):

```php
Route::get('/api/command-palette/search', \App\Http\Controllers\Search\CommandPaletteSearchController::class)
    ->name('command-palette.search');
```

Run `php artisan permission:sync-routes`. Re-run tests → expect PASS, 3 tests.

Commit: `feat(search): expose command-palette search endpoint`

---

### Task 4: Frontend store + types + shortcut hook

**`resources/js/stores/command-palette-store.ts`:**

```ts
import { create } from 'zustand';

interface CommandPaletteState {
    isOpen: boolean;
    open: () => void;
    close: () => void;
    toggle: () => void;
}

export const useCommandPaletteStore = create<CommandPaletteState>((set) => ({
    isOpen: false,
    open: () => set({ isOpen: true }),
    close: () => set({ isOpen: false }),
    toggle: () => set((state) => ({ isOpen: !state.isOpen })),
}));
```

**`resources/js/components/command-palette/types.ts`:**

```ts
export interface RakeHit {
    id: number;
    rake_number: string;
    siding_name: string | null;
    status: string | null;
}

export interface IndentHit {
    id: number;
    indent_number: string;
    e_demand_number: string | null;
}

export interface RrHit {
    id: number;
    rr_number: string;
    rake_id: number | null;
}

export interface SearchResponse {
    rakes: RakeHit[];
    indents: IndentHit[];
    rrs: RrHit[];
}

export interface StaticAction {
    id: string;
    label: string;
    keywords?: string[];
    href: string;
    hint?: string;
}
```

**`resources/js/components/command-palette/use-command-shortcut.ts`:**

```ts
import { useEffect } from 'react';
import { useCommandPaletteStore } from '@/stores/command-palette-store';

export function useCommandShortcut() {
    const toggle = useCommandPaletteStore((s) => s.toggle);

    useEffect(() => {
        const handler = (event: KeyboardEvent) => {
            if ((event.metaKey || event.ctrlKey) && event.key.toLowerCase() === 'k') {
                event.preventDefault();
                toggle();
            }
        };

        window.addEventListener('keydown', handler);
        return () => window.removeEventListener('keydown', handler);
    }, [toggle]);
}
```

Commit: `feat(ui): scaffold command palette state + hook`

---

### Task 5: Static actions

**Step 1 — Verify each `href` exists:**

```bash
php artisan route:list --except-vendor 2>&1 | grep -E "dashboard|rakes|indents|alerts|penalty"
```

Drop any entry whose route isn't on the branch.

**File:** `resources/js/components/command-palette/static-actions.ts`

```ts
import type { StaticAction } from './types';

export const STATIC_ACTIONS: StaticAction[] = [
    {
        id: 'go-dashboard',
        label: 'Go to Dashboard',
        keywords: ['home', 'overview'],
        href: '/dashboard',
    },
    {
        id: 'go-rakes',
        label: 'Go to Rakes',
        keywords: ['list', 'wagons'],
        href: '/rakes',
    },
    {
        id: 'go-indents',
        label: 'Go to Indents',
        keywords: ['orders', 'demand'],
        href: '/indents',
    },
    {
        id: 'go-alerts',
        label: 'Go to Alerts',
        keywords: ['notifications', 'overload'],
        href: '/alerts',
    },
];
```

Commit: `feat(ui): seed static actions for command palette`

---

### Task 6: `CommandPalette` component

**File:** `resources/js/components/command-palette/command-palette.tsx`

```tsx
import {
    CommandDialog,
    CommandEmpty,
    CommandGroup,
    CommandInput,
    CommandItem,
    CommandList,
    CommandSeparator,
} from '@/components/ui/command';
import { useCommandPaletteStore } from '@/stores/command-palette-store';
import { router } from '@inertiajs/react';
import { Layers, Receipt, Search, Train } from 'lucide-react';
import { useEffect, useMemo, useRef, useState } from 'react';
import { STATIC_ACTIONS } from './static-actions';
import type { SearchResponse } from './types';
import { useCommandShortcut } from './use-command-shortcut';

const EMPTY_RESULTS: SearchResponse = { rakes: [], indents: [], rrs: [] };

export function CommandPalette() {
    useCommandShortcut();

    const isOpen = useCommandPaletteStore((s) => s.isOpen);
    const close = useCommandPaletteStore((s) => s.close);

    const [query, setQuery] = useState('');
    const [results, setResults] = useState<SearchResponse>(EMPTY_RESULTS);
    const [loading, setLoading] = useState(false);
    const debounceRef = useRef<ReturnType<typeof setTimeout> | null>(null);
    const abortRef = useRef<AbortController | null>(null);

    useEffect(() => {
        if (!isOpen) {
            setQuery('');
            setResults(EMPTY_RESULTS);
        }
    }, [isOpen]);

    useEffect(() => {
        if (debounceRef.current) {
            clearTimeout(debounceRef.current);
        }
        if (abortRef.current) {
            abortRef.current.abort();
        }

        if (query.trim().length < 2) {
            setResults(EMPTY_RESULTS);
            setLoading(false);
            return;
        }

        debounceRef.current = setTimeout(() => {
            const controller = new AbortController();
            abortRef.current = controller;
            setLoading(true);

            fetch(`/api/command-palette/search?q=${encodeURIComponent(query)}`, {
                headers: { Accept: 'application/json' },
                signal: controller.signal,
                credentials: 'same-origin',
            })
                .then((res) => (res.ok ? res.json() : EMPTY_RESULTS))
                .then((data: SearchResponse) => {
                    setResults(data);
                    setLoading(false);
                })
                .catch((err) => {
                    if (err.name !== 'AbortError') {
                        setResults(EMPTY_RESULTS);
                    }
                    setLoading(false);
                });
        }, 200);

        return () => {
            if (debounceRef.current) clearTimeout(debounceRef.current);
        };
    }, [query]);

    const filteredActions = useMemo(() => {
        if (!query) return STATIC_ACTIONS;
        const q = query.toLowerCase();
        return STATIC_ACTIONS.filter((a) =>
            a.label.toLowerCase().includes(q) ||
            (a.keywords ?? []).some((k) => k.toLowerCase().includes(q)),
        );
    }, [query]);

    const navigate = (href: string) => {
        close();
        router.visit(href);
    };

    return (
        <CommandDialog
            open={isOpen}
            onOpenChange={(o) => (o ? null : close())}
            title="Command palette"
            description="Search rakes, indents, RRs, or jump to a page."
        >
            <CommandInput
                placeholder="Type a rake number, RR, indent, or action…"
                value={query}
                onValueChange={setQuery}
            />
            <CommandList>
                {!loading && query.length >= 2 &&
                    results.rakes.length === 0 &&
                    results.indents.length === 0 &&
                    results.rrs.length === 0 &&
                    filteredActions.length === 0 && (
                        <CommandEmpty>No results.</CommandEmpty>
                    )}

                {filteredActions.length > 0 && (
                    <>
                        <CommandGroup heading="Jump to">
                            {filteredActions.map((action) => (
                                <CommandItem
                                    key={action.id}
                                    value={`${action.label} ${(action.keywords ?? []).join(' ')}`}
                                    onSelect={() => navigate(action.href)}
                                >
                                    <Search className="mr-2 h-4 w-4 text-slate-500" />
                                    <span>{action.label}</span>
                                </CommandItem>
                            ))}
                        </CommandGroup>
                        <CommandSeparator />
                    </>
                )}

                {results.rakes.length > 0 && (
                    <CommandGroup heading="Rakes">
                        {results.rakes.map((rake) => (
                            <CommandItem
                                key={`rake-${rake.id}`}
                                value={`rake ${rake.rake_number}`}
                                onSelect={() => navigate(`/rakes/${rake.id}`)}
                            >
                                <Train className="mr-2 h-4 w-4 text-blue-600" />
                                <span className="font-mono">{rake.rake_number}</span>
                                {rake.siding_name && (
                                    <span className="ml-2 text-xs text-slate-500">{rake.siding_name}</span>
                                )}
                            </CommandItem>
                        ))}
                    </CommandGroup>
                )}

                {results.indents.length > 0 && (
                    <CommandGroup heading="Indents">
                        {results.indents.map((indent) => (
                            <CommandItem
                                key={`indent-${indent.id}`}
                                value={`indent ${indent.indent_number}`}
                                onSelect={() => navigate(`/indents/${indent.id}`)}
                            >
                                <Layers className="mr-2 h-4 w-4 text-emerald-600" />
                                <span className="font-mono">{indent.indent_number}</span>
                                {indent.e_demand_number && (
                                    <span className="ml-2 text-xs text-slate-500">e-Demand {indent.e_demand_number}</span>
                                )}
                            </CommandItem>
                        ))}
                    </CommandGroup>
                )}

                {results.rrs.length > 0 && (
                    <CommandGroup heading="Railway Receipts">
                        {results.rrs.map((rr) => (
                            <CommandItem
                                key={`rr-${rr.id}`}
                                value={`rr ${rr.rr_number}`}
                                onSelect={() => navigate(rr.rake_id ? `/rakes/${rr.rake_id}` : `/rr/${rr.id}`)}
                            >
                                <Receipt className="mr-2 h-4 w-4 text-amber-600" />
                                <span className="font-mono">{rr.rr_number}</span>
                            </CommandItem>
                        ))}
                    </CommandGroup>
                )}
            </CommandList>
        </CommandDialog>
    );
}
```

Run `npx tsc --noEmit`. Adjust `router.visit` path if Inertia API differs in this project.

Commit: `feat(ui): add CommandPalette component`

---

### Task 7: Component test

**File:** `resources/js/components/command-palette/command-palette.test.tsx`

```tsx
import { render, screen } from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import { afterEach, beforeEach, describe, expect, it, vi } from 'vitest';
import { CommandPalette } from './command-palette';
import { useCommandPaletteStore } from '@/stores/command-palette-store';

describe('CommandPalette', () => {
    beforeEach(() => {
        useCommandPaletteStore.setState({ isOpen: true });
    });

    afterEach(() => {
        useCommandPaletteStore.setState({ isOpen: false });
        vi.restoreAllMocks();
    });

    it('shows static actions on initial open', () => {
        render(<CommandPalette />);
        expect(screen.getByText('Go to Dashboard')).toBeInTheDocument();
        expect(screen.getByText('Go to Rakes')).toBeInTheDocument();
    });

    it('filters static actions by typed query', async () => {
        const user = userEvent.setup();
        render(<CommandPalette />);

        await user.type(screen.getByRole('combobox'), 'rake');
        expect(screen.getByText('Go to Rakes')).toBeInTheDocument();
        expect(screen.queryByText('Go to Dashboard')).not.toBeInTheDocument();
    });

    it('queries the search endpoint with debounced input', async () => {
        const fetchSpy = vi.spyOn(global, 'fetch').mockResolvedValue(
            new Response(JSON.stringify({
                rakes: [{ id: 1, rake_number: 'DUMK-1234', siding_name: 'Dumka', status: null }],
                indents: [],
                rrs: [],
            }), { status: 200, headers: { 'Content-Type': 'application/json' } }),
        );

        const user = userEvent.setup();
        render(<CommandPalette />);

        await user.type(screen.getByRole('combobox'), 'DUMK');
        await new Promise((r) => setTimeout(r, 250));

        expect(fetchSpy).toHaveBeenCalledWith(
            expect.stringContaining('/api/command-palette/search?q=DUMK'),
            expect.any(Object),
        );

        expect(await screen.findByText('DUMK-1234')).toBeInTheDocument();
    });
});
```

If `@testing-library/user-event` is missing: `npm install --save-dev @testing-library/user-event`.

Run `npx vitest run resources/js/components/command-palette/command-palette.test.tsx` → expect PASS, 3 tests.

Commit: `test(ui): cover CommandPalette behaviour`

---

### Task 8: Mount globally

**Step 1 — Confirm shell:**

Run: `find resources/js/layouts -type f -name "*.tsx" | head -10`. Identify the file that wraps every authenticated page.

**Step 2 — Mount the palette:**

Inside the shell's JSX, near the closing tag, add:

```tsx
import { CommandPalette } from '@/components/command-palette/command-palette';

// just before the closing </AppShell> (or whichever root):
<CommandPalette />
```

**Step 3 — Smoke-test.** Boot dev. Press Cmd+K from any page → palette opens. Type `DUMK` → results appear. Esc closes.

Commit: `feat(ui): mount CommandPalette globally`

---

### Task 9: Documentation

**`docs/developer/frontend/components/command-palette.md`:**

```markdown
# Command Palette

Global Cmd+K (Ctrl+K) palette. Mounted once in the global shell.

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

## Adding a static action

Append to `STATIC_ACTIONS`. Use a Wayfinder route helper for `href` once relevant routes ship.

## Adding a new searchable category

1. Extend `CommandPaletteResults` with a new array property.
2. Add a `WHERE ILIKE` block to `SearchForCommandPaletteAction::handle()`.
3. Render a new `<CommandGroup>` in `command-palette.tsx`.
4. Add a test case to `SearchForCommandPaletteActionTest`.

## Performance

200ms debounce, ≤10 hits per category. PostgreSQL `ILIKE` over indexed columns. If categories grow past ~100k rows, switch to Scout / Typesense — only the Action changes.
```

**`docs/user-guide/keyboard-shortcuts.md`:**

```markdown
# Keyboard shortcuts

| Shortcut | Action |
|---|---|
| **Cmd + K** (macOS) / **Ctrl + K** (Windows / Linux) | Open the command palette |
| **Esc** (with palette open) | Close the palette |
| **↑ / ↓** (with palette open) | Navigate suggestions |
| **Enter** (with palette open) | Open the highlighted item |

## Command palette

Type two or more characters to search. The palette finds:
- **Rakes** by rake number
- **Indents** by indent number or e-Demand number
- **Railway Receipts** by RR number

You can also jump to common pages by typing the page name (Dashboard, Rakes, Indents, Alerts).
```

Run `php artisan docs:sync`.

Commit: `docs: command palette + keyboard shortcuts`

---

### Task 10: Pint + Prettier + final test sweep

- `vendor/bin/pint --dirty --format agent`
- `npx prettier --write resources/js/components/command-palette/ resources/js/stores/command-palette-store.ts`
- `npm run build`
- `php artisan test --filter='SearchForCommandPalette|CommandPaletteSearch' --compact`
- `npx vitest run resources/js/components/command-palette/`

Commit any formatting changes: `style: pint + prettier sweep`. Otherwise skip.

---

## Self-Review

- **Spec coverage:** Backend (Tasks 1–3), frontend store/types/hook (Task 4), static actions (Task 5), component (Task 6), tests (Tasks 2, 3, 7), global mount (Task 8), docs (Task 9), wrap-up (Task 10). ✅
- **No third-party APIs.** Uses internal DB only. ✅
- **Type consistency:** `RakeHit` / `IndentHit` / `RrHit` keys in `types.ts` match the JSON shape returned by the controller and the array maps in the action. `useCommandPaletteStore` API consistent across hook, component, and test. ✅

---

## Execution Notes

- **Activation:** automatic — once mounted in the shell, every authenticated page exposes Cmd+K.
- **Rollback:** `git revert` of each commit.
- **Future:** swap `ILIKE` for Scout / Typesense (already configured for `App\Models\Rake`) when the corpus grows; only the Action changes.
