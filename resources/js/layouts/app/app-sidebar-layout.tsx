import { AppContent } from '@/components/app-content';
import { AppShell } from '@/components/app-shell';
import { AppSidebar } from '@/components/app-sidebar';
import { AppSidebarHeader } from '@/components/app-sidebar-header';
import { CloseSidebarOnNavigate } from '@/components/close-sidebar-on-navigate';
import { CommandPalette } from '@/components/command-dialog';
import { type BreadcrumbItem } from '@/types';
import { type PropsWithChildren } from 'react';
import { useEffect } from 'react';
import { usePage } from '@inertiajs/react';
import { type SharedData } from '@/types';

export default function AppSidebarLayout({
    children,
    breadcrumbs = [],
}: PropsWithChildren<{ breadcrumbs?: BreadcrumbItem[] }>) {
    const fleetOnly = Boolean(usePage<SharedData>().props.fleet_only_app);
    useEffect(() => {
        if (fleetOnly) document.body.classList.add('fleet-no-decor-active');
        return () => document.body.classList.remove('fleet-no-decor-active');
    }, [fleetOnly]);
    return (
        <div className={fleetOnly ? 'fleet-no-decor-wrapper' : ''}>
            <AppShell variant="sidebar">
                <CloseSidebarOnNavigate />
                <CommandPalette />
                <AppSidebar />
                <AppContent
                    variant="sidebar"
                    className={`overflow-x-hidden ${fleetOnly ? 'fleet-no-decor' : ''}`}
                >
                    <AppSidebarHeader breadcrumbs={breadcrumbs} />
                    {children}
                </AppContent>
            </AppShell>
        </div>
    );
}
