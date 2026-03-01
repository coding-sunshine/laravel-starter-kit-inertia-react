import '../css/app.css';
import './echo';

import { createInertiaApp } from '@inertiajs/react';
import { resolvePageComponent } from 'laravel-vite-plugin/inertia-helpers';
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
    title: (title) => (title ? `${title} - ${appName}` : appName),
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
                        <Page {...props} />
                        <Toaster richColors position="top-right" />
                    </>
                );
            };
        }),
    setup({ el, App, props }) {
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
