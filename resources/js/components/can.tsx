import type { ReactNode } from 'react';
import { useCan } from '@/hooks/use-can';

interface CanProps {
    /** Permission name, or array of names (children render if user has any). */
    permission: string | string[];
    children: ReactNode;
}

/**
 * Renders children only when the current user has the given permission(s).
 * Use with shared auth.permissions (and auth.can_bypass for super-admin).
 */
export function Can({ permission, children }: CanProps) {
    const allowed = useCan(permission);

    if (!allowed) {
        return null;
    }

    return <>{children}</>;
}
