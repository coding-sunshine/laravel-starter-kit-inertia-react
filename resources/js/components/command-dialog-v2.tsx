'use client';

import {
    CommandDialog,
    CommandEmpty,
    CommandGroup,
    CommandInput,
    CommandItem,
    CommandList,
    CommandShortcut,
} from '@/components/ui/command';
import { Skeleton } from '@/components/ui/skeleton';
import { fleetDashboardItem, fleetNavSections } from '@/config/fleet-nav';
import { dashboard, logout } from '@/routes';
import { index as blogIndex } from '@/routes/blog';
import { index as changelogIndex } from '@/routes/changelog';
import { create as contactCreate } from '@/routes/contact';
import { index as helpIndex } from '@/routes/help';
import organizations from '@/routes/organizations';
import { edit as editUserProfile } from '@/routes/user-profile';
import { type NavItem, type SharedData } from '@/types';
import { router, usePage } from '@inertiajs/react';
import { getHotkeyManager } from '@tanstack/hotkeys';
import {
    Building2,
    CreditCard,
    FileText,
    LayoutGrid,
    LifeBuoy,
    LogOut,
    Mail,
    Megaphone,
    Settings,
    Star,
    Truck,
    Users,
} from 'lucide-react';
import { useCallback, useEffect, useMemo, useRef, useState } from 'react';

const RECENT_KEY = 'command-palette-recent';
const FAVORITES_KEY = 'command-palette-favorites';
const MAX_RECENT = 5;

interface RecentEntry {
    title: string;
    url: string;
}

interface SearchResult {
    id: number;
    title: string;
    subtitle: string;
    url: string;
    type: string;
}

interface SearchResults {
    users: SearchResult[];
    posts: SearchResult[];
    help_articles: SearchResult[];
    changelog_entries: SearchResult[];
}

const CATEGORY_CONFIG = {
    users: { label: 'Users', icon: Users },
    posts: { label: 'Posts', icon: FileText },
    help_articles: { label: 'Help Articles', icon: LifeBuoy },
    changelog_entries: { label: 'Changelog', icon: Megaphone },
} as const;

type CategoryKey = keyof typeof CATEGORY_CONFIG;

const mainNavItems: NavItem[] = [
    { title: 'Dashboard', href: dashboard().url, icon: LayoutGrid },
    {
        title: 'Organizations',
        href: organizations.index.url(),
        icon: Building2,
        tenancyRequired: true,
    },
    { title: 'Fleet', href: '/fleet', icon: Truck, tenancyRequired: true },
    { title: 'Billing', href: '/billing', icon: CreditCard },
    {
        title: 'Blog',
        href: blogIndex().url,
        icon: FileText,
        permission: 'blog.index',
        feature: 'blog',
    },
    {
        title: 'Changelog',
        href: changelogIndex().url,
        icon: Megaphone,
        permission: 'changelog.index',
        feature: 'changelog',
    },
    {
        title: 'Help',
        href: helpIndex().url,
        icon: LifeBuoy,
        permission: 'help.index',
        feature: 'help',
    },
    {
        title: 'Contact',
        href: contactCreate().url,
        icon: Mail,
        permission: 'contact.create',
        feature: 'contact',
    },
];

function canShowNavItem(
    item: NavItem,
    permissions: string[],
    canBypass: boolean,
    features: SharedData['features'],
    tenancyEnabled: boolean,
): boolean {
    if (item.tenancyRequired && !tenancyEnabled) return false;
    if (item.feature && !features?.[item.feature]) return false;
    if (canBypass || !item.permission) return true;
    const required = Array.isArray(item.permission)
        ? item.permission
        : [item.permission];
    return required.some((p) => permissions.includes(p));
}

function getRecent(): RecentEntry[] {
    try {
        const raw = sessionStorage.getItem(RECENT_KEY);
        if (!raw) return [];
        const parsed = JSON.parse(raw) as RecentEntry[];
        return Array.isArray(parsed) ? parsed.slice(0, MAX_RECENT) : [];
    } catch {
        return [];
    }
}

function pushRecent(entry: RecentEntry): void {
    try {
        const list = getRecent().filter((e) => e.url !== entry.url);
        list.unshift(entry);
        sessionStorage.setItem(
            RECENT_KEY,
            JSON.stringify(list.slice(0, MAX_RECENT)),
        );
    } catch {
        //
    }
}

function getFavorites(): RecentEntry[] {
    try {
        const raw = localStorage.getItem(FAVORITES_KEY);
        if (!raw) return [];
        const parsed = JSON.parse(raw) as RecentEntry[];
        return Array.isArray(parsed) ? parsed : [];
    } catch {
        return [];
    }
}

function _toggleFavorite(_entry: RecentEntry): void {
    try {
        const list = getFavorites();
        const idx = list.findIndex((e) => e.url === entry.url);
        const next =
            idx >= 0
                ? list.filter((_, i) => i !== idx)
                : [...list, entry].slice(-20);
        localStorage.setItem(FAVORITES_KEY, JSON.stringify(next));
    } catch {
        //
    }
}

function SearchResultSkeleton() {
    return (
        <div className="space-y-3 p-4">
            {Array.from({ length: 3 }, (_, i) => (
                <div key={i} className="flex items-center gap-3">
                    <Skeleton className="size-5 rounded" />
                    <div className="flex-1 space-y-1.5">
                        <Skeleton className="h-3.5 w-3/4" />
                        <Skeleton className="h-3 w-1/2" />
                    </div>
                </div>
            ))}
        </div>
    );
}

export function CommandPaletteV2(): React.ReactElement {
    const [open, setOpen] = useState(false);
    const [query, setQuery] = useState('');
    const [results, setResults] = useState<SearchResults | null>(null);
    const [isSearching, setIsSearching] = useState(false);
    const [recent, setRecent] = useState<RecentEntry[]>([]);
    const [favorites, setFavorites] = useState<RecentEntry[]>([]);
    const debounceRef = useRef<ReturnType<typeof setTimeout>>(undefined);
    const abortRef = useRef<AbortController>(undefined);
    const { auth, features, fleet_only_app } = usePage<SharedData>().props;
    const fleetOnly = Boolean(fleet_only_app);

    const visibleNavItems = useMemo(
        () =>
            mainNavItems.filter((item) =>
                canShowNavItem(
                    item,
                    auth.permissions ?? [],
                    auth.can_bypass ?? false,
                    features ?? {},
                    auth.tenancy_enabled ?? true,
                ),
            ),
        [auth.permissions, auth.can_bypass, auth.tenancy_enabled, features],
    );

    useEffect(() => {
        const manager = getHotkeyManager();
        const handle = manager.register('Mod+k', () => setOpen((o) => !o));
        return () => handle.unregister();
    }, []);

    useEffect(() => {
        const handler = () => setOpen(true);
        window.addEventListener('open-command-palette', handler);
        return () =>
            window.removeEventListener('open-command-palette', handler);
    }, []);

    useEffect(() => {
        if (!open) {
            setQuery('');
            setResults(null);
            setIsSearching(false);
        } else {
            setRecent(getRecent());
            setFavorites(getFavorites());
        }
    }, [open]);

    useEffect(() => {
        if (debounceRef.current) clearTimeout(debounceRef.current);
        if (abortRef.current) abortRef.current.abort();

        if (query.length === 0) {
            setResults(null);
            setIsSearching(false);
            return;
        }

        setIsSearching(true);

        debounceRef.current = setTimeout(() => {
            const controller = new AbortController();
            abortRef.current = controller;

            fetch(`/search?q=${encodeURIComponent(query)}`, {
                signal: controller.signal,
                headers: {
                    Accept: 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                },
            })
                .then((res) => res.json())
                .then((data: SearchResults) => {
                    if (!controller.signal.aborted) {
                        setResults(data);
                        setIsSearching(false);
                    }
                })
                .catch(() => {
                    if (!abortRef.current?.signal.aborted) {
                        setIsSearching(false);
                    }
                });
        }, 300);

        return () => {
            if (debounceRef.current) clearTimeout(debounceRef.current);
        };
    }, [query]);

    const run = useCallback(
        (href: string, isLogout = false, title?: string) => {
            setOpen(false);
            if (title && href) {
                pushRecent({ title, url: href });
            }
            if (isLogout) {
                router.post(href);
            } else {
                router.visit(href);
            }
        },
        [],
    );

    const openAssistant = useCallback(() => {
        setOpen(false);
        router.visit('/fleet/assistant');
    }, []);

    const logoutUrl =
        typeof logout() === 'string'
            ? logout()
            : (logout() as { url: string }).url;
    const isSearchMode = query.length > 0;
    const hasResults =
        results && Object.values(results).some((arr) => arr.length > 0);

    const fleetShortcuts = useMemo(() => {
        const items = [
            fleetDashboardItem,
            ...fleetNavSections.flatMap((s) => s.items),
        ].slice(0, 12);
        return items;
    }, []);

    return (
        <CommandDialog
            open={open}
            onOpenChange={setOpen}
            title="Command Palette"
            description="Search, navigate, or ask the Fleet Assistant"
            shouldFilter={!isSearchMode}
            data-pan="command-palette-v2"
        >
            <CommandInput
                placeholder="Search or type a command..."
                value={query}
                onValueChange={setQuery}
            />
            <CommandList>
                {isSearchMode ? (
                    <>
                        {isSearching && <SearchResultSkeleton />}
                        {!isSearching && !hasResults && (
                            <CommandEmpty>
                                No results for &ldquo;{query}&rdquo;
                            </CommandEmpty>
                        )}
                        {!isSearching &&
                            results &&
                            (Object.keys(CATEGORY_CONFIG) as CategoryKey[]).map(
                                (key) => {
                                    const config = CATEGORY_CONFIG[key];
                                    const items = results[key];
                                    if (!items || items.length === 0)
                                        return null;
                                    const Icon = config.icon;
                                    return (
                                        <CommandGroup
                                            key={key}
                                            heading={config.label}
                                        >
                                            {items.map((item) => (
                                                <CommandItem
                                                    key={`${item.type}-${item.id}`}
                                                    value={`${item.type}-${item.id}-${item.title}`}
                                                    onSelect={() =>
                                                        run(
                                                            item.url,
                                                            false,
                                                            item.title,
                                                        )
                                                    }
                                                >
                                                    <Icon className="size-4 shrink-0" />
                                                    <div className="flex min-w-0 flex-1 flex-col">
                                                        <span className="truncate">
                                                            {item.title}
                                                        </span>
                                                        {item.subtitle && (
                                                            <span className="truncate text-xs text-muted-foreground">
                                                                {item.subtitle}
                                                            </span>
                                                        )}
                                                    </div>
                                                    <span className="ml-auto shrink-0 text-xs text-muted-foreground capitalize">
                                                        {item.type.replace(
                                                            '_',
                                                            ' ',
                                                        )}
                                                    </span>
                                                </CommandItem>
                                            ))}
                                        </CommandGroup>
                                    );
                                },
                            )}
                    </>
                ) : (
                    <>
                        {favorites.length > 0 && (
                            <CommandGroup heading="Favorites">
                                {favorites.map((entry) => (
                                    <CommandItem
                                        key={entry.url}
                                        value={`fav-${entry.url}`}
                                        onSelect={() =>
                                            run(entry.url, false, entry.title)
                                        }
                                    >
                                        <Star className="size-4 fill-amber-400 text-amber-500" />
                                        {entry.title}
                                    </CommandItem>
                                ))}
                            </CommandGroup>
                        )}
                        {recent.length > 0 && (
                            <CommandGroup heading="Recent">
                                {recent.map((entry) => (
                                    <CommandItem
                                        key={entry.url}
                                        value={`recent-${entry.url}-${entry.title}`}
                                        onSelect={() =>
                                            run(entry.url, false, entry.title)
                                        }
                                    >
                                        <LayoutGrid className="size-4 shrink-0 opacity-60" />
                                        {entry.title}
                                    </CommandItem>
                                ))}
                            </CommandGroup>
                        )}
                        {(fleetOnly || auth.tenancy_enabled) && (
                            <CommandGroup heading="Fleet">
                                {fleetShortcuts.map((item) => {
                                    const Icon = item.icon ?? Truck;
                                    const href =
                                        typeof item.href === 'string'
                                            ? item.href
                                            : item.href;
                                    return (
                                        <CommandItem
                                            key={item.href}
                                            value={item.title}
                                            onSelect={() =>
                                                run(href, false, item.title)
                                            }
                                        >
                                            <Icon className="size-4 shrink-0" />
                                            {item.title}
                                        </CommandItem>
                                    );
                                })}
                            </CommandGroup>
                        )}
                        <CommandGroup heading="AI">
                            <CommandItem
                                value="Ask Fleet Assistant"
                                onSelect={openAssistant}
                            >
                                <Truck className="size-4" />
                                Ask Fleet Assistant
                                <CommandShortcut>⌘K then type</CommandShortcut>
                            </CommandItem>
                        </CommandGroup>
                        <CommandGroup heading="Navigation">
                            {visibleNavItems.map((item) => {
                                const Icon = item.icon;
                                const href =
                                    typeof item.href === 'string'
                                        ? item.href
                                        : (
                                                item.href as {
                                                    url?:
                                                        | string
                                                        | (() => string);
                                                }
                                            ).url !== undefined
                                          ? typeof (
                                                item.href as {
                                                    url:
                                                        | string
                                                        | (() => string);
                                                }
                                            ).url === 'function'
                                              ? (
                                                    item.href as {
                                                        url: () => string;
                                                    }
                                                ).url()
                                              : (item.href as { url: string })
                                                    .url
                                          : '#';
                                return (
                                    <CommandItem
                                        key={item.title}
                                        value={item.title}
                                        onSelect={() =>
                                            run(href, false, item.title)
                                        }
                                    >
                                        {Icon && (
                                            <Icon className="size-4 shrink-0" />
                                        )}
                                        {item.title}
                                    </CommandItem>
                                );
                            })}
                        </CommandGroup>
                        <CommandGroup heading="Account">
                            <CommandItem
                                value="Settings"
                                onSelect={() =>
                                    run(
                                        editUserProfile().url,
                                        false,
                                        'Settings',
                                    )
                                }
                            >
                                <Settings className="size-4" />
                                Settings
                            </CommandItem>
                            <CommandItem
                                value="Log out"
                                onSelect={() => run(logoutUrl, true)}
                            >
                                <LogOut className="size-4" />
                                Log out
                            </CommandItem>
                        </CommandGroup>
                    </>
                )}
            </CommandList>
        </CommandDialog>
    );
}
