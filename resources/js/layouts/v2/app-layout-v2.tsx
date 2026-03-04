import { AppContent } from '@/components/app-content';
import { AppShell } from '@/components/app-shell';
import { AppSidebar } from '@/components/app-sidebar';
import { AppSidebarHeader } from '@/components/app-sidebar-header';
import { CloseSidebarOnNavigate } from '@/components/close-sidebar-on-navigate';
import { CommandPaletteV2 } from '@/components/command-dialog-v2';
import { FleetAssistantFab } from '@/components/fleet';
import { type BreadcrumbItem, type SharedData } from '@/types';
import { usePage } from '@inertiajs/react';
import { useEffect, type PropsWithChildren } from 'react';

/**
 * App layout v2: vertical nav, command palette (recent, favorites, fleet shortcuts, AI), FAB.
 * Uses design tokens and ui-v2 when migrating pages. Data attribute for v2 styling.
 */
export default function AppLayoutV2({
    children,
    breadcrumbs = [],
}: PropsWithChildren<{ breadcrumbs?: BreadcrumbItem[] }>) {
    const fleetOnly = Boolean(usePage<SharedData>().props.fleet_only_app);

    useEffect(() => {
        if (fleetOnly) document.body.classList.add('fleet-no-decor-active');
        return () => document.body.classList.remove('fleet-no-decor-active');
    }, [fleetOnly]);

    return (
        <div
            className={fleetOnly ? 'fleet-no-decor-wrapper' : ''}
            data-layout-version="v2"
        >
            <AppShell variant="sidebar">
                <CloseSidebarOnNavigate />
                <CommandPaletteV2 />
                <AppSidebar />
                <AppContent
                    variant="sidebar"
                    className={`overflow-x-hidden ${fleetOnly ? 'fleet-no-decor' : ''}`}
                >
                    <AppSidebarHeader breadcrumbs={breadcrumbs} />
                    {children}
                </AppContent>
                <FleetAssistantFab />
            </AppShell>
        </div>
    );
}
