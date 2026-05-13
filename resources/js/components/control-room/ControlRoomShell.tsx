import { router } from '@inertiajs/react';
import { Maximize2, Minimize2, RefreshCcw } from 'lucide-react';
import { useEffect, useState, type ReactNode } from 'react';

import { StaleIndicator } from '@/components/control-room/StaleIndicator';
import type { StaleStatus } from '@/components/control-room/types';
import { Button } from '@/components/ui/button';

interface ControlRoomShellProps {
    title: string;
    subtitle?: string;
    staleStatus: StaleStatus;
    secondsSince: number | null;
    headerActions?: ReactNode;
    children: ReactNode;
}

export function ControlRoomShell({
    title,
    subtitle,
    staleStatus,
    secondsSince,
    headerActions,
    children,
}: ControlRoomShellProps) {
    const [isFullscreen, setIsFullscreen] = useState(false);

    useEffect(() => {
        const handler = () =>
            setIsFullscreen(Boolean(document.fullscreenElement));
        document.addEventListener('fullscreenchange', handler);

        return () => document.removeEventListener('fullscreenchange', handler);
    }, []);

    const toggleFullscreen = async (): Promise<void> => {
        if (typeof document === 'undefined') {
            return;
        }
        if (document.fullscreenElement) {
            await document.exitFullscreen();
        } else {
            await document.documentElement.requestFullscreen();
        }
    };

    return (
        <div className="flex min-h-full flex-col">
            <header className="sticky top-0 z-10 flex flex-wrap items-center justify-between gap-3 border-b border-border bg-background px-4 py-3 lg:px-6">
                <div className="min-w-0">
                    <h1 className="text-lg font-semibold tracking-tight text-foreground">
                        {title}
                    </h1>
                    {subtitle && (
                        <p className="truncate text-xs text-muted-foreground">
                            {subtitle}
                        </p>
                    )}
                </div>
                <div className="flex items-center gap-2">
                    <StaleIndicator
                        status={staleStatus}
                        secondsSince={secondsSince}
                    />
                    {headerActions}
                    <Button
                        type="button"
                        variant="outline"
                        size="sm"
                        onClick={() => router.reload()}
                        title="Refresh now"
                    >
                        <RefreshCcw className="size-4" aria-hidden="true" />
                    </Button>
                    <Button
                        type="button"
                        variant="outline"
                        size="sm"
                        onClick={toggleFullscreen}
                        title={isFullscreen ? 'Exit fullscreen' : 'Fullscreen'}
                    >
                        {isFullscreen ? (
                            <Minimize2 className="size-4" aria-hidden="true" />
                        ) : (
                            <Maximize2 className="size-4" aria-hidden="true" />
                        )}
                    </Button>
                </div>
            </header>
            <main className="flex-1 bg-muted/30 p-4 lg:p-6">{children}</main>
        </div>
    );
}

export default ControlRoomShell;
