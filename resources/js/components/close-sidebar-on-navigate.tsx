'use client';

import { useSidebar } from '@/components/ui/sidebar';
import { usePage } from '@inertiajs/react';
import { useEffect } from 'react';

/**
 * Closes the mobile sidebar drawer when the user navigates (UI-06).
 * Must be rendered inside SidebarProvider.
 */
export function CloseSidebarOnNavigate() {
    const { url } = usePage();
    const { setOpenMobile } = useSidebar();

    useEffect(() => {
        setOpenMobile(false);
    }, [url, setOpenMobile]);

    return null;
}
