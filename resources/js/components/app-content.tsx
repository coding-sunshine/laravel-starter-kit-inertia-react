import { SidebarInset } from '@/components/ui/sidebar';
import * as React from 'react';

interface AppContentProps extends React.ComponentProps<'main'> {
    variant?: 'header' | 'sidebar';
}

export function AppContent({
    variant = 'header',
    children,
    id,
    ...props
}: AppContentProps) {
    const mainId = id ?? 'main-content';

    if (variant === 'sidebar') {
        return (
            <SidebarInset id={mainId} {...props}>
                {children}
            </SidebarInset>
        );
    }

    return (
        <main
            id={mainId}
            className="mx-auto flex h-full w-full max-w-7xl flex-1 flex-col gap-4 rounded-xl"
            {...props}
        >
            {children}
        </main>
    );
}
