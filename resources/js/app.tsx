import '../css/app.css';
import './echo';

import { PageTransition } from '@/components/motion/page-transition';
import { createInertiaApp } from '@inertiajs/react';
import { resolvePageComponent } from 'laravel-vite-plugin/inertia-helpers';
import { AnimatePresence } from 'framer-motion';
import type { ReactNode } from 'react';
import { createRoot } from 'react-dom/client';
import { Toaster } from 'sonner';
import { CookieConsentBanner } from './components/cookie-consent-banner';
import { FlashListener } from './components/flash-listener';
import { ThemeFromProps } from './components/theme-from-props';
import { initializeTheme } from './hooks/use-appearance';
import { GoogleMapsProvider } from './providers/google-maps-provider';
import { QueryProvider } from './providers/query-provider';

const appName = import.meta.env.VITE_APP_NAME || 'Laravel';

// Explicit entries for Fleet workflow pages so they resolve when glob misses them (e.g. project path with space).
const workflowPages: Record<string, () => Promise<{ default: unknown }>> = {
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
    ...workflowPages,
};

createInertiaApp({
    title: (title) => {
        const name = (typeof window !== 'undefined' && (window as { __INERTIA_APP_TITLE?: string }).__INERTIA_APP_TITLE) || appName;
        return title ? `${title} - ${name}` : name;
    },
    resolve: (name) =>
        resolvePageComponent(
            `./pages/${name}.tsx`,
            allPages,
        ).then((module) => {
            const Page = module.default;
            return function PageWithCookieBanner(
                props: Record<string, unknown>,
            ): ReactNode {
                return (
                    <>
                        <ThemeFromProps />
                        <CookieConsentBanner />
                        <FlashListener />
                        <AnimatePresence mode="wait">
                            <PageTransition>
                                <Page {...props} />
                            </PageTransition>
                        </AnimatePresence>
                        <Toaster richColors position="top-right" />
                    </>
                );
            };
        }),
    setup({ el, App, props }) {
        const p = props as { fleet_only_app?: boolean; name?: string };
        if (p.fleet_only_app) (window as { __INERTIA_APP_TITLE?: string }).__INERTIA_APP_TITLE = 'Fleet Management';
        else (window as { __INERTIA_APP_TITLE?: string }).__INERTIA_APP_TITLE = p.name || appName;

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
        color: '#4B5563',
    },
});

// This will set light / dark mode on load...
initializeTheme();
