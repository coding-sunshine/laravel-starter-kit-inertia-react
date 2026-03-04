import Heading from '@/components/heading';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Separator } from '@/components/ui/separator';
import { cn } from '@/lib/utils';
import { onboarding } from '@/routes';
import { show as showAchievements } from '@/routes/achievements';
import { edit as editAppearance } from '@/routes/appearance';
import { edit as editPassword } from '@/routes/password';
import { edit as editPersonalDataExport } from '@/routes/personal-data-export';
import { edit as editBranding } from '@/routes/settings/branding';
import { show } from '@/routes/two-factor';
import { edit } from '@/routes/user-profile';
import { type NavItem, type SharedData } from '@/types';
import { Link, usePage } from '@inertiajs/react';
import { ChevronRight, Search } from 'lucide-react';
import { type PropsWithChildren, useMemo, useState } from 'react';

const sidebarNavItems: (NavItem & { feature?: string; dataPan: string })[] = [
    {
        title: 'Profile',
        href: edit(),
        icon: null,
        dataPan: 'settings-nav-profile',
    },
    {
        title: 'Password',
        href: editPassword(),
        icon: null,
        dataPan: 'settings-nav-password',
    },
    {
        title: 'Two-Factor Auth',
        href: show(),
        icon: null,
        feature: 'two_factor_auth',
        dataPan: 'settings-nav-two-factor',
    },
    {
        title: 'Appearance',
        href: editAppearance(),
        icon: null,
        feature: 'appearance_settings',
        dataPan: 'settings-nav-appearance',
    },
    {
        title: 'Organization branding',
        href: editBranding(),
        icon: null,
        dataPan: 'settings-nav-branding',
    },
    {
        title: 'Data export',
        href: editPersonalDataExport(),
        icon: null,
        feature: 'personal_data_export',
        dataPan: 'settings-nav-data-export',
    },
    {
        title: 'Level & achievements',
        href: showAchievements(),
        icon: null,
        feature: 'gamification',
        dataPan: 'settings-nav-achievements',
    },
    {
        title: 'Onboarding',
        href: onboarding(),
        icon: null,
        feature: 'onboarding',
        dataPan: 'settings-nav-onboarding',
    },
];

export default function SettingsLayout({ children }: PropsWithChildren) {
    const { features } = usePage<SharedData>().props;
    const [search, setSearch] = useState('');

    const visibleNavItems = useMemo(() => {
        const f = features ?? {};
        return sidebarNavItems.filter((item) => {
            if (!item.feature) return true;
            const value = f[item.feature];
            return value === true || value === 1;
        });
    }, [features]);

    const filteredNavItems = useMemo(() => {
        if (!search.trim()) return visibleNavItems;
        const q = search.toLowerCase().trim();
        return visibleNavItems.filter((item) =>
            item.title.toLowerCase().includes(q),
        );
    }, [visibleNavItems, search]);

    // When server-side rendering, we only render the layout on the client...
    if (typeof window === 'undefined') {
        return null;
    }

    const currentPath = window.location.pathname;
    const currentItem = visibleNavItems.find(
        (item) =>
            (typeof item.href === 'string' ? item.href : item.href.url) ===
            currentPath,
    );
    const settingsHref =
        visibleNavItems[0] &&
        (typeof visibleNavItems[0].href === 'string'
            ? visibleNavItems[0].href
            : visibleNavItems[0].href.url);
    const breadcrumbs = currentItem
        ? [
              { title: 'Settings', href: settingsHref ?? '#' },
              { title: currentItem.title, href: null as string | null },
          ]
        : [{ title: 'Settings', href: settingsHref ?? '#' }];

    return (
        <div className="px-4 py-6">
            <Heading
                title="Settings"
                description="Manage your profile and account settings"
            />

            <nav
                className="mb-4 flex items-center gap-2 text-sm text-muted-foreground"
                aria-label="Breadcrumb"
            >
                {breadcrumbs.map((b, i) => (
                    <span key={i} className="flex items-center gap-2">
                        {b.href ? (
                            <Link
                                href={b.href}
                                className="hover:text-foreground"
                            >
                                {b.title}
                            </Link>
                        ) : (
                            <span className="text-foreground">{b.title}</span>
                        )}
                        {i < breadcrumbs.length - 1 && (
                            <ChevronRight className="size-4" />
                        )}
                    </span>
                ))}
            </nav>

            <div className="flex flex-col lg:flex-row lg:space-x-12">
                <aside className="w-full max-w-xl lg:w-52">
                    <div className="relative mb-2">
                        <Search className="absolute top-1/2 left-2.5 size-4 -translate-y-1/2 text-muted-foreground" />
                        <Input
                            type="search"
                            placeholder="Search settings…"
                            value={search}
                            onChange={(e) => setSearch(e.target.value)}
                            className="pl-8"
                            aria-label="Search settings"
                        />
                    </div>
                    <nav className="flex flex-col space-y-1 space-x-0">
                        {filteredNavItems.map((item) => (
                            <Button
                                key={
                                    typeof item.href === 'string'
                                        ? item.href
                                        : item.href.url
                                }
                                size="sm"
                                variant="ghost"
                                asChild
                                data-pan={item.dataPan}
                                className={cn('w-full justify-start', {
                                    'bg-muted':
                                        currentPath ===
                                        (typeof item.href === 'string'
                                            ? item.href
                                            : item.href.url),
                                })}
                            >
                                <Link href={item.href}>
                                    {item.icon && (
                                        <item.icon className="h-4 w-4" />
                                    )}
                                    {item.title}
                                </Link>
                            </Button>
                        ))}
                    </nav>
                </aside>

                <Separator className="my-6 lg:hidden" />

                <div className="flex-1 md:max-w-2xl">
                    <section className="max-w-xl space-y-12">
                        {children}
                    </section>
                </div>
            </div>
        </div>
    );
}
