import { useEffect, useState } from 'react';

export function OfflineBanner() {
    const [isOnline, setIsOnline] = useState(
        typeof navigator !== 'undefined' ? navigator.onLine : true,
    );

    useEffect(() => {
        const onOnline = () => setIsOnline(true);
        const onOffline = () => setIsOnline(false);
        window.addEventListener('online', onOnline);
        window.addEventListener('offline', onOffline);
        return () => {
            window.removeEventListener('online', onOnline);
            window.removeEventListener('offline', onOffline);
        };
    }, []);

    if (isOnline) return null;

    return (
        <div
            role="alert"
            aria-live="assertive"
            className="fixed right-0 bottom-0 left-0 z-50 border-t border-amber-200 bg-amber-50 px-4 py-3 pb-[max(0.75rem,env(safe-area-inset-bottom))] text-center text-sm text-amber-900 dark:border-amber-800 dark:bg-amber-950 dark:text-amber-100"
        >
            You are offline. Some data may not be up to date. Reconnect to sync.
        </div>
    );
}
