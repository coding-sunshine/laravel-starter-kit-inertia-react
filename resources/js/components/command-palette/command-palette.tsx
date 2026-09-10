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

            fetch(
                `/api/command-palette/search?q=${encodeURIComponent(query)}`,
                {
                    headers: { Accept: 'application/json' },
                    signal: controller.signal,
                    credentials: 'same-origin',
                },
            )
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
        return STATIC_ACTIONS.filter(
            (a) =>
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
                {!loading &&
                    query.length >= 2 &&
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
                                <span className="font-mono">
                                    {rake.rake_number}
                                </span>
                                {rake.siding_name && (
                                    <span className="ml-2 text-xs text-slate-500">
                                        {rake.siding_name}
                                    </span>
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
                                onSelect={() =>
                                    navigate(`/indents/${indent.id}`)
                                }
                            >
                                <Layers className="mr-2 h-4 w-4 text-emerald-600" />
                                <span className="font-mono">
                                    {indent.indent_number}
                                </span>
                                {indent.e_demand_number && (
                                    <span className="ml-2 text-xs text-slate-500">
                                        e-Demand {indent.e_demand_number}
                                    </span>
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
                                onSelect={() =>
                                    navigate(
                                        rr.rake_id
                                            ? `/rakes/${rr.rake_id}`
                                            : `/rr/${rr.id}`,
                                    )
                                }
                            >
                                <Receipt className="mr-2 h-4 w-4 text-amber-600" />
                                <span className="font-mono">
                                    {rr.rr_number}
                                </span>
                            </CommandItem>
                        ))}
                    </CommandGroup>
                )}
            </CommandList>
        </CommandDialog>
    );
}
