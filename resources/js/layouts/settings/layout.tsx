import Heading from '@/components/heading';
import { Button } from '@/components/ui/button';
import { Separator } from '@/components/ui/separator';
import { cn } from '@/lib/utils';
import { type NavItem } from '@/types';
import { Link } from '@inertiajs/react';
import { type PropsWithChildren, useMemo } from 'react';

export default function SettingsLayout({ children }: PropsWithChildren) {
    const visibleNavItems = useMemo(() => [], []);

    // When server-side rendering, we only render the layout on the client...
    if (typeof window === 'undefined') {
        return null;
    }

    return (
        <div className="py-6">
            <Heading
                title="Settings"
                description="Manage your profile and account settings"
            />

            <div className="flex flex-col lg:flex-row lg:space-x-12">
                {visibleNavItems.length > 0 && (
                    <>
                        <aside className="w-full max-w-xl lg:w-48">
                            <nav className="flex flex-col space-y-1 space-x-0">
                                {visibleNavItems.map((item: NavItem & { dataPan?: string }) => (
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
                                        className={cn('w-full justify-start')}
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
                    </>
                )}

                <div className="flex-1 md:max-w-2xl">
                    <section className="max-w-xl space-y-8">{children}</section>
                </div>
            </div>
        </div>
    );
}
