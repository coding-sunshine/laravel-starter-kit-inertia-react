import { Button } from '@/components/ui/button';
import { accept } from '@/routes/cookie-consent';
import { type SharedData } from '@/types';
import { router, usePage } from '@inertiajs/react';

interface CookieConsentProps {
    accepted: boolean;
    cookieName: string;
    lifetimeDays: number;
}

export function CookieConsentBanner() {
    const { props } = usePage<
        SharedData & { cookieConsent: CookieConsentProps | null }
    >();
    const { cookieConsent, features } = props;

    if (!features?.cookie_consent || !cookieConsent || cookieConsent.accepted) {
        return null;
    }

    const handleAccept = () => {
        router.visit(accept().url, { method: 'get' });
    };

    return (
        <div
            className="fixed right-0 bottom-0 left-0 z-50 border-t border-border bg-background/95 p-4 shadow-lg backdrop-blur supports-[backdrop-filter]:bg-background/80"
            role="dialog"
            aria-label="Cookie consent"
        >
            <div className="mx-auto flex max-w-4xl flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                <p className="text-sm text-muted-foreground">
                    We use cookies to remember your preferences and improve your
                    experience. By clicking Accept, you consent to our use of
                    cookies.
                </p>
                <Button size="sm" onClick={handleAccept} className="shrink-0">
                    Accept
                </Button>
            </div>
        </div>
    );
}
