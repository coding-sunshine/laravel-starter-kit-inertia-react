import Heading from '@/components/heading';
import { Button } from '@/components/ui/button';
import { Separator } from '@/components/ui/separator';
import { cn } from '@/lib/utils';
import { edit as editPassword } from '@/routes/password';
import { edit } from '@/routes/user-profile';
import { type NavItem } from '@/types';
import { Link } from '@inertiajs/react';
import { type PropsWithChildren, useMemo } from 'react';

const sidebarNavItems: (NavItem & { dataPan: string })[] = [
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
];

export default function SettingsLayout({ children }: PropsWithChildren) {
    const visibleNavItems = useMemo(() => sidebarNavItems, []);

    const currentPath =
        typeof window !== 'undefined' ? window.location.pathname : '';

    return (
        <div className="py-6">
            <Heading
                title="Settings"
                description="Manage your profile and account settings"
            />

            <div className="flex flex-col lg:flex-row lg:space-x-12">
                <aside className="w-full max-w-xl lg:w-48">
                    <nav className="flex flex-col space-y-1 space-x-0">
                        {visibleNavItems.map((item) => (
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
                                        (typeof item.href === 'string'
                                            ? item.href
                                            : item.href.url) === currentPath,
                                })}
                            >
                                <Link href={item.href}>{item.title}</Link>
                            </Button>
                        ))}
                    </nav>
                </aside>

                <Separator className="my-6 lg:hidden" />

                <div className="flex-1 md:max-w-2xl">
                    <section className="max-w-xl space-y-8">{children}</section>
                </div>
            </div>
        </div>
    );
}
