'use client';

import { Button } from '@/components/ui/button';
import { Download, X } from 'lucide-react';
import { useCallback, useEffect, useState } from 'react';

type BeforeInstallPromptEvent = Event & {
    prompt: () => Promise<{ outcome: 'accepted' | 'dismissed' }>;
    userChoice: Promise<{ outcome: 'accepted' | 'dismissed' }>;
};

const SESSION_KEY = 'pwa-install-prompt-dismissed';

function isStandalone(): boolean {
    if (typeof window === 'undefined') return false;
    return (
        window.matchMedia('(display-mode: standalone)').matches ||
        (window.navigator as { standalone?: boolean }).standalone === true
    );
}

export function PwaInstallPrompt() {
    const [deferredPrompt, setDeferredPrompt] =
        useState<BeforeInstallPromptEvent | null>(null);
    const [visible, setVisible] = useState(false);
    const [dismissed, setDismissed] = useState(false);

    useEffect(() => {
        if (isStandalone()) return;

        const handler = (e: Event) => {
            e.preventDefault();
            setDeferredPrompt(e as BeforeInstallPromptEvent);
        };
        window.addEventListener('beforeinstallprompt', handler);
        return () => window.removeEventListener('beforeinstallprompt', handler);
    }, []);

    useEffect(() => {
        if (!deferredPrompt || dismissed || isStandalone()) return;
        if (
            typeof sessionStorage !== 'undefined' &&
            sessionStorage.getItem(SESSION_KEY) === '1'
        )
            return;

        const timer = setTimeout(() => setVisible(true), 3000);
        return () => clearTimeout(timer);
    }, [deferredPrompt, dismissed]);

    const handleInstall = useCallback(async () => {
        if (!deferredPrompt) return;
        await deferredPrompt.prompt();
        const { outcome } = await deferredPrompt.userChoice;
        if (outcome === 'accepted') setVisible(false);
        setDeferredPrompt(null);
    }, [deferredPrompt]);

    const handleDismiss = useCallback(() => {
        setVisible(false);
        setDismissed(true);
        try {
            sessionStorage.setItem(SESSION_KEY, '1');
        } catch {
            /* ignore */
        }
    }, []);

    if (!visible || !deferredPrompt) return null;

    return (
        <div
            className="fixed right-4 bottom-4 left-4 z-50 flex flex-wrap items-center justify-between gap-3 rounded-lg border border-border bg-card px-4 py-3 shadow-lg sm:right-4 sm:left-auto sm:max-w-sm"
            role="dialog"
            aria-label="Install app"
        >
            <p className="text-sm text-foreground">
                Install this app for quick access from your home screen.
            </p>
            <div className="flex items-center gap-2">
                <Button size="sm" onClick={handleInstall} className="gap-1.5">
                    <Download className="size-4" />
                    Install
                </Button>
                <Button
                    size="sm"
                    variant="ghost"
                    onClick={handleDismiss}
                    aria-label="Dismiss"
                >
                    <X className="size-4" />
                </Button>
            </div>
        </div>
    );
}
