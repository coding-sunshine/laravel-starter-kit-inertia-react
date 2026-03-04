import '../css/app.css';
import './echo';

import { ErrorBoundary } from '@/components/error-boundary';
import { PageTransition } from '@/components/motion/page-transition';
import { createInertiaApp } from '@inertiajs/react';
import { AnimatePresence } from 'framer-motion';
import { resolvePageComponent } from 'laravel-vite-plugin/inertia-helpers';
import type { ReactNode } from 'react';
import { createRoot } from 'react-dom/client';
import { Toaster } from 'sonner';
import { CookieConsentBanner } from './components/cookie-consent-banner';
import { FlashListener } from './components/flash-listener';
import { OfflineBanner } from './components/offline-banner';
import { PwaInstallPrompt } from './components/pwa-install-prompt';
import { ThemeFromProps } from './components/theme-from-props';
import { initializeTheme } from './hooks/use-appearance';
import { GoogleMapsProvider } from './providers/google-maps-provider';
import { QueryProvider } from './providers/query-provider';

const appName = import.meta.env.VITE_APP_NAME || 'Laravel';

// Explicit entries for Fleet pages so they resolve when glob misses them (e.g. project path with space).
const fleetExplicitPages: Record<string, () => Promise<{ default: unknown }>> =
    {
        './pages/Fleet/Vehicles/Index.tsx': () =>
            import('./pages/Fleet/Vehicles/Index.tsx'),
        './pages/Fleet/Vehicles/Show.tsx': () =>
            import('./pages/Fleet/Vehicles/Show.tsx'),
        './pages/Fleet/Vehicles/Create.tsx': () =>
            import('./pages/Fleet/Vehicles/Create.tsx'),
        './pages/Fleet/Vehicles/Edit.tsx': () =>
            import('./pages/Fleet/Vehicles/Edit.tsx'),
        './pages/Fleet/Drivers/Index.tsx': () =>
            import('./pages/Fleet/Drivers/Index.tsx'),
        './pages/Fleet/Drivers/Show.tsx': () =>
            import('./pages/Fleet/Drivers/Show.tsx'),
        './pages/Fleet/Drivers/Create.tsx': () =>
            import('./pages/Fleet/Drivers/Create.tsx'),
        './pages/Fleet/Drivers/Edit.tsx': () =>
            import('./pages/Fleet/Drivers/Edit.tsx'),
        './pages/Fleet/WorkOrders/Index.tsx': () =>
            import('./pages/Fleet/WorkOrders/Index.tsx'),
        './pages/Fleet/WorkOrders/Show.tsx': () =>
            import('./pages/Fleet/WorkOrders/Show.tsx'),
        './pages/Fleet/WorkOrders/Create.tsx': () =>
            import('./pages/Fleet/WorkOrders/Create.tsx'),
        './pages/Fleet/WorkOrders/Edit.tsx': () =>
            import('./pages/Fleet/WorkOrders/Edit.tsx'),
        './pages/Fleet/Assistant/Index.tsx': () =>
            import('./pages/Fleet/Assistant/Index.tsx'),
        './pages/Fleet/DriverVehicleAssignments/Index.tsx': () =>
            import('./pages/Fleet/DriverVehicleAssignments/Index.tsx'),
        './pages/Fleet/Defects/Index.tsx': () =>
            import('./pages/Fleet/Defects/Index.tsx'),
        './pages/Fleet/Defects/Show.tsx': () =>
            import('./pages/Fleet/Defects/Show.tsx'),
        './pages/Fleet/Routes/Index.tsx': () =>
            import('./pages/Fleet/Routes/Index.tsx'),
        './pages/Fleet/Trips/Index.tsx': () =>
            import('./pages/Fleet/Trips/Index.tsx'),
        './pages/Fleet/Dashboard.tsx': () =>
            import('./pages/Fleet/Dashboard.tsx'),
        './pages/Fleet/WorkflowDefinitions/Index.tsx': () =>
            import('./pages/Fleet/WorkflowDefinitions/Index.tsx'),
        './pages/Fleet/WorkflowDefinitions/Create.tsx': () =>
            import('./pages/Fleet/WorkflowDefinitions/Create.tsx'),
        './pages/Fleet/WorkflowDefinitions/Show.tsx': () =>
            import('./pages/Fleet/WorkflowDefinitions/Show.tsx'),
        './pages/Fleet/WorkflowDefinitions/Edit.tsx': () =>
            import('./pages/Fleet/WorkflowDefinitions/Edit.tsx'),
    };
const allPages = {
    ...import.meta.glob('./pages/**/*.tsx'),
    ...fleetExplicitPages,
};

createInertiaApp({
    title: (title) => {
        const name =
            (typeof window !== 'undefined' &&
                (window as { __INERTIA_APP_TITLE?: string })
                    .__INERTIA_APP_TITLE) ||
            appName;
        return title ? `${title} - ${name}` : name;
    },
    resolve: (name) =>
        resolvePageComponent(`./pages/${name}.tsx`, allPages).then((module) => {
            const Page = module.default;
            return function PageWithCookieBanner(
                props: Record<string, unknown>,
            ): ReactNode {
                return (
                    <ErrorBoundary>
                        <ThemeFromProps />
                        <CookieConsentBanner />
                        <FlashListener />
                        <OfflineBanner />
                        <PwaInstallPrompt />
                        <AnimatePresence mode="wait">
                            <PageTransition>
                                <Page {...props} />
                            </PageTransition>
                        </AnimatePresence>
                        <Toaster richColors position="top-right" />
                    </ErrorBoundary>
                );
            };
        }),
    setup({ el, App, props }) {
        const p = props as { fleet_only_app?: boolean; name?: string };
        if (p.fleet_only_app)
            (window as { __INERTIA_APP_TITLE?: string }).__INERTIA_APP_TITLE =
                'Fleet Management';
        else
            (window as { __INERTIA_APP_TITLE?: string }).__INERTIA_APP_TITLE =
                p.name || appName;

        const root = createRoot(el);

        root.render(
            <QueryProvider>
                <GoogleMapsProvider>
                    <App {...props} />
                </GoogleMapsProvider>
            </QueryProvider>,
        );
    },
    progress: {
        color: 'hsl(var(--primary))',
        delay: 250,
        showSpinner: true,
    },
});

// This will set light / dark mode on load...
initializeTheme();

if (typeof window !== 'undefined' && 'serviceWorker' in navigator) {
    window.addEventListener('load', () => {
        navigator.serviceWorker.register('/sw.js').catch(() => {});
    });
}
